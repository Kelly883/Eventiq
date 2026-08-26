<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    /**
     * Access levels a user may self-request. Each maps to a canonical
     * Permission row that is created on first request so the foreign key
     * always resolves.
     */
    private const REQUESTABLE_ACCESS = [
        'admin-access' => 'Requests administrative access to the platform',
        'organizer-access' => 'Requests organizer capabilities for event management',
    ];

    public function __construct()
    {
        // Admin-only access for most methods
    }

    public function index()
    {
        // List all permissions
        return response()->json([
            'data' => Permission::orderBy('name')->get(),
        ]);
    }

    public function updateRolePermissions(Request $request, $roleId)
    {
        $role = \App\Models\Role::findOrFail($roleId);

        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->permissions()->sync($validated['permissions']);

        return response()->json(['message' => 'Role permissions updated successfully.']);
    }

    public function auditLog()
    {
        // Get audit log
        return response()->json(['data' => []]);
    }

    public function getPermissionRequests()
    {
        $requests = PermissionRequest::with(['user:id,name,email', 'permission:id,name'])
            ->latest()
            ->limit(200)
            ->get();

        return response()->json(['data' => $requests]);
    }

    public function approvePermissionRequest(PermissionRequest $request)
    {
        return $this->resolveRequest($request, PermissionRequest::STATUS_APPROVED);
    }

    public function rejectPermissionRequest(Request $httpRequest, PermissionRequest $request)
    {
        $validated = $httpRequest->validate([
            'approvalReason' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->resolveRequest($request, PermissionRequest::STATUS_DENIED, $validated['approvalReason'] ?? null);
    }

    private function resolveRequest(PermissionRequest $request, string $status, ?string $reason = null)
    {
        if (!$request->isPending()) {
            return response()->json([
                'message' => 'This request has already been resolved.',
            ], 409);
        }

        $request->update([
            'status' => $status,
            'approvedBy' => auth()->id(),
            'approvalReason' => $reason,
            'resolvedAt' => now(),
        ]);

        return response()->json([
            'message' => "Permission request {$status}.",
            'data' => $request->fresh()->load(['user:id,name,email', 'permission:id,name']),
        ]);
    }

    public function submitPermissionRequest(Request $request)
    {
        $validated = $request->validate([
            'requestedPermission' => ['required', 'string', Rule::in(array_keys(self::REQUESTABLE_ACCESS))],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $permission = Permission::firstOrCreate(
            ['name' => $validated['requestedPermission']],
            [
                'description' => self::REQUESTABLE_ACCESS[$validated['requestedPermission']],
                'group' => 'access',
            ]
        );

        $duplicate = PermissionRequest::where('userId', $request->user()->id)
            ->where('permissionId', $permission->id)
            ->where('status', PermissionRequest::STATUS_PENDING)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'You already have a pending request for this access level. An administrator will review it soon.',
            ], 409);
        }

        $permissionRequest = PermissionRequest::create([
            'userId' => $request->user()->id,
            'permissionId' => $permission->id,
            'reason' => $validated['reason'],
            'status' => PermissionRequest::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'Your access request has been submitted for review.',
            'data' => $permissionRequest,
        ], 201);
    }
}
