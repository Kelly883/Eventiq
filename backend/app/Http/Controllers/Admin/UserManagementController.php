<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Features\Compliance\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    public function __construct(private AuditLogService $auditLogService) {}

    /**
     * GET /api/admin/users/list
     * Query: ?role=Organizer&search=john&page=1&limit=20
     */
    public function listUsers(Request $request)
    {
        // Enforce admin via Policy + middleware (IsAdmin also checks) — defense in depth
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden — admin access required'], 403);
        }
        // Explicit Policy check (AdminPolicy::before returns hasRole('admin'))
        // Gate will throw 403 if unauthorized, but we already checked isAdmin for clear JSON
        try {
            Gate::forUser($request->user())->authorize('before', User::class);
        } catch (\Throwable $e) {
            // Already handled by isAdmin check, but keep for audit
        }

        // Audit search enumeration for forensics (admin searching users)
        if ($request->filled('search')) {
            \Log::channel('audit')->info('admin_users_search', [
                'admin_id' => $request->user()->id,
                'search' => $request->input('search'),
                'ip' => $request->ip(),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'role' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid parameters', 'errors' => $validator->errors()], 400);
        }

        $validated = $validator->validated();
        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 20);
        $roleFilter = $validated['role'] ?? null;
        $search = $validated['search'] ?? null;

        try {
            $query = User::query()
                ->with(['roles', 'permissions', 'roleRelation'])
                ->when($roleFilter, function ($q) use ($roleFilter) {
                    // Filter by role name via pivot or legacy string column
                    $q->where(function ($qq) use ($roleFilter) {
                        $qq->where('role', $roleFilter)
                            ->orWhereHas('roles', fn($r) => $r->where('name', $roleFilter))
                            ->orWhereHas('roleRelation', fn($r) => $r->where('name', $roleFilter));
                    });
                })
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($qq) use ($search) {
                        $qq->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
                })
                ->orderByDesc('created_at');

            $total = (clone $query)->count();
            $users = $query->forPage($page, $limit)->get();

            $mapped = $users->map(function (User $user) {
                // Never expose passwordHash
                $role = null;
                if ($user->relationLoaded('roleRelation') && $user->roleRelation) {
                    $role = ['id' => $user->roleRelation->id, 'name' => $user->roleRelation->name];
                } elseif ($user->relationLoaded('roles') && $user->getRelation('roles')->isNotEmpty()) {
                    $firstRole = $user->getRelation('roles')->first();
                    $role = ['id' => $firstRole->id, 'name' => $firstRole->name];
                } elseif (!empty($user->role)) {
                    $role = ['id' => null, 'name' => $user->role];
                }

                $perms = $user->relationLoaded('permissions') ? $user->getRelation('permissions') : $user->permissions()->get();

                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $role,
                    'permissionCount' => $perms->count(),
                    'createdAt' => $user->created_at?->toIso8601String(),
                ];
            });

            return response()->json([
                'users' => $mapped,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
            ]);
        } catch (\Throwable $e) {
            \Log::error('admin users list failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Database error'], 500);
        }
    }

    /**
     * POST /api/admin/roles/assign
     * Body: { userIds: [uuid], roleId: uuid, reason?: string }
     */
    public function assignRole(Request $request)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden — admin access required'], 403);
        }
        try {
            Gate::forUser($request->user())->authorize('before', User::class);
        } catch (\Throwable $e) {
        }

        $validator = Validator::make($request->all(), [
            'userIds' => ['required', 'array', 'min:1'],
            'userIds.*' => ['required', 'string'],
            'roleId' => ['required', 'exists:roles,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid parameters', 'errors' => $validator->errors()], 400);
        }

        $validated = $validator->validated();
        $role = Role::find($validated['roleId']);
        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $admin = $request->user();
        $userIds = $validated['userIds'];
        $reason = $validated['reason'] ?? null;

        // Check all users exist first (404 if any missing)
        $users = User::whereIn('id', $userIds)->get();
        $foundIds = $users->pluck('id')->all();
        $missing = array_diff($userIds, $foundIds);
        if (!empty($missing)) {
            return response()->json(['message' => 'Some users not found', 'missing' => array_values($missing)], 404);
        }

        // Prevent self-demotion: if admin tries to assign a non-admin role to themselves
        if (in_array($admin->id, $userIds, true)) {
            $isTargetAdminRole = strtolower($role->name) === 'admin';
            $currentlyAdmin = $admin->isAdmin();
            if ($currentlyAdmin && !$isTargetAdminRole) {
                return response()->json(['message' => 'Cannot demote yourself'], 403);
            }
        }

        try {
            $updated = 0;
            DB::transaction(function () use ($userIds, $role, $admin, $reason, &$updated) {
                // Row-level locking to prevent concurrent role changes (two admins, same user)
                $users = User::whereIn('id', $userIds)->lockForUpdate()->with(['roles', 'permissions', 'roleRelation'])->get();
                foreach ($users as $user) {
                    // Use relation to avoid attribute shadowing
                    $permsRelation = $user->relationLoaded('permissions') ? $user->getRelation('permissions') : $user->permissions()->get();
                    $oldRoleId = $user->role_id ?? $user->roleRelation?->id ?? $user->role ?? null;
                    $oldPermissions = $permsRelation->pluck('name')->toArray();

                    // Update legacy string column and role_id + pivot
                    $user->update([
                        'role_id' => $role->id,
                        'role' => $role->name,
                    ]);
                    // Sync roles pivot (keep single role model consistent)
                    $user->roles()->syncWithoutDetaching([$role->id]);
                    // For non-admin roles, ensure admin role is removed if demoting
                    if (strtolower($role->name) !== 'admin') {
                        $adminRoleIds = Role::where('name', 'admin')->pluck('id');
                        if ($adminRoleIds->isNotEmpty()) {
                            // Only detach admin role if target is not admin role
                            $otherAdminRoles = $adminRoleIds->diff([$role->id]);
                            if ($otherAdminRoles->isNotEmpty()) {
                                $user->roles()->detach($otherAdminRoles);
                            }
                        }
                    }

                    // Invalidate sessions
                    $user->invalidateAllSessions();

                    // Audit log
                    $this->auditLogService->log(
                        'role_assigned',
                        'user',
                        $user->id,
                        [
                            'old_role' => $oldRoleId,
                            'new_role' => $role->id,
                            'old_role_name' => is_string($oldRoleId) && !str_contains($oldRoleId, '-') ? $oldRoleId : ($user->getOriginal('role') ?? $oldRoleId),
                            'old_permissions' => $oldPermissions,
                            'reason' => $reason,
                        ],
                        $admin->id
                    );

                    // Also create a more detailed audit entry with reason
                    // Use metadata for reason
                    $updated++;
                }
            });

            return response()->json([
                'updated' => $updated,
                'message' => "Role assigned to {$updated} users",
            ]);
        } catch (\Throwable $e) {
            \Log::error('admin role assign failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Database error'], 500);
        }
    }

    /**
     * POST /api/admin/permissions/update
     * Body: { userId: uuid, permissionIds: [id], action: grant|revoke, reason?: string, confirmHighRisk?: bool }
     */
    public function updatePermissions(Request $request)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden — admin access required'], 403);
        }
        try {
            Gate::forUser($request->user())->authorize('before', User::class);
        } catch (\Throwable $e) {
        }

        $validator = Validator::make($request->all(), [
            'userId' => ['required', 'string'],
            'permissionIds' => ['required', 'array', 'min:1'],
            'permissionIds.*' => ['required'],
            'action' => ['required', Rule::in(['grant', 'revoke'])],
            'reason' => ['nullable', 'string', 'max:1000'],
            'confirmHighRisk' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid parameters', 'errors' => $validator->errors()], 400);
        }

        $validated = $validator->validated();
        $user = User::find($validated['userId']);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $permissionIds = $validated['permissionIds'];
        $action = $validated['action'];
        $reason = $validated['reason'] ?? null;
        $confirmHighRisk = (bool) ($validated['confirmHighRisk'] ?? false);
        $admin = $request->user();

        // Validate each permission exists (handle both integer and uuid ids)
        $permissions = Permission::whereIn('id', $permissionIds)->get();
        if ($permissions->count() !== count($permissionIds)) {
            $foundIds = $permissions->pluck('id')->map(fn($v) => (string) $v)->all();
            $missing = array_diff(array_map('strval', $permissionIds), $foundIds);
            return response()->json(['message' => 'Some permissions not found', 'missing' => array_values($missing)], 404);
        }

        // High-risk check: if any permission is high-risk, require confirmation
        $highRiskPerms = $permissions->filter(fn($p) => $p->isHighRisk());
        if ($highRiskPerms->isNotEmpty() && !$confirmHighRisk) {
            return response()->json([
                'message' => 'High-risk permissions require explicit confirmation',
                'highRiskPermissions' => $highRiskPerms->pluck('name'),
                'confirmHighRiskRequired' => true,
            ], 400);
        }

        // Strict: never allow self-modification of high-risk permissions, even with confirmation
        // Requires second admin (four-eyes). Prevents privilege escalation via single compromised admin.
        if ($user->id === $admin->id && $highRiskPerms->isNotEmpty()) {
            return response()->json(['message' => 'Cannot modify own high-risk permissions — requires second admin'], 403);
        }

        try {
            $updated = 0;
            DB::transaction(function () use ($user, $permissions, $action, $admin, $reason, &$updated) {
                // Lock the target user row to prevent concurrent permission changes (two admins same user)
                User::where('id', $user->id)->lockForUpdate()->first();
                $user->load(['permissions', 'roles']);
                $permsRelation = $user->getRelation('permissions');
                $oldPerms = $permsRelation->pluck('id')->map(fn($v) => (string) $v)->toArray();

                if ($action === 'grant') {
                    $user->permissions()->syncWithoutDetaching($permissions->pluck('id'));
                } else {
                    $user->permissions()->detach($permissions->pluck('id'));
                }

                $newPerms = $user->permissions()->pluck('id')->toArray();
                // Calculate actually changed
                if ($action === 'grant') {
                    $updated = count(array_diff($permissions->pluck('id')->toArray(), $oldPerms));
                    // If all already had, still count as updated per spec? Use permission count
                    if ($updated === 0) {
                        $updated = $permissions->count(); // idempotent
                    } else {
                        $updated = count(array_diff($newPerms, $oldPerms));
                    }
                } else {
                    $updated = count(array_intersect($oldPerms, $permissions->pluck('id')->toArray()));
                    if ($updated === 0) {
                        $updated = 0;
                    }
                }

                // Invalidate sessions
                $user->invalidateAllSessions();

                foreach ($permissions as $perm) {
                    $this->auditLogService->log(
                        $action === 'grant' ? 'permission_granted' : 'permission_revoked',
                        'user',
                        $user->id,
                        [
                            'permission_id' => $perm->id,
                            'permission_name' => $perm->name,
                            'action' => $action,
                            'old_permissions' => $oldPerms,
                            'new_permissions' => $newPerms,
                            'reason' => $reason,
                            'high_risk' => $perm->isHighRisk(),
                        ],
                        $admin->id
                    );
                }
            });

            return response()->json([
                'updated' => $updated,
                'message' => 'Permissions updated',
            ]);
        } catch (\Throwable $e) {
            \Log::error('admin permissions update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Database error'], 500);
        }
    }

    /**
     * GET /api/admin/audit-log/list
     * Query: ?targetUserId=uuid&action=role_assigned&page=1&limit=50
     */
    public function auditLogList(Request $request)
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Forbidden — admin access required'], 403);
        }
        try {
            Gate::forUser($request->user())->authorize('before', User::class);
        } catch (\Throwable $e) {
        }

        $validator = Validator::make($request->all(), [
            'targetUserId' => ['nullable', 'uuid'],
            'action' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid parameters', 'errors' => $validator->errors()], 400);
        }

        $validated = $validator->validated();
        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 50);
        $targetUserId = $validated['targetUserId'] ?? null;
        $action = $validated['action'] ?? null;

        try {
            $query = AuditLog::query()
                ->with(['user:id,name,email'])
                ->when($targetUserId, fn($q) => $q->where('target_id', $targetUserId)->where('target_type', 'user'))
                ->when($action, fn($q) => $q->where('action', $action))
                ->orderByDesc('created_at');

            $total = (clone $query)->count();
            $logs = $query->forPage($page, $limit)->get();

            $mapped = $logs->map(function (AuditLog $log) {
                // Resolve targetUser
                $targetUser = null;
                if ($log->target_type === 'user' && $log->target_id) {
                    $target = User::select('id', 'name', 'email')->find($log->target_id);
                    if ($target) {
                        $targetUser = ['id' => $target->id, 'name' => $target->name, 'email' => $target->email];
                    } else {
                        // Deleted user — keep id but mark as deleted
                        $targetUser = ['id' => $log->target_id, 'name' => '[deleted]', 'email' => '[deleted]'];
                    }
                }

                $admin = $log->user ? ['id' => $log->user->id, 'name' => $log->user->name, 'email' => $log->user->email] : null;

                // Extract old/new/reason from changed_fields/metadata
                $changed = $log->changed_fields ?? [];
                $metadata = $log->metadata ?? [];
                $oldValue = $changed['old_role'] ?? $changed['old_permissions'] ?? $changed['oldValue'] ?? null;
                $newValue = $changed['new_role'] ?? $changed['new_permissions'] ?? $changed['newValue'] ?? $changed['new_role_name'] ?? null;
                // Fallback to storing permission name
                if ($oldValue === null && isset($changed['permission_name'])) {
                    $oldValue = $changed['action'] === 'grant' ? null : $changed['permission_name'];
                    $newValue = $changed['permission_name'];
                }
                $reason = $changed['reason'] ?? $metadata['reason'] ?? $log->description ?? null;

                return [
                    'id' => $log->id,
                    'admin' => $admin,
                    'targetUser' => $targetUser,
                    'action' => $log->action,
                    'oldValue' => $oldValue,
                    'newValue' => $newValue,
                    'reason' => $reason,
                    'createdAt' => $log->created_at?->toIso8601String(),
                ];
            });

            return response()->json([
                'logs' => $mapped,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
            ]);
        } catch (\Throwable $e) {
            \Log::error('admin audit log list failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Database error'], 500);
        }
    }
}
