<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct()
    {
    }

    public function index(): JsonResponse
    {
        $roles = Role::query()
            ->with(['permissions', 'users'])
            ->orderBy('name')
            ->get()
            ->map(function ($role) {
                $permissions = $role->getRelation('permissions') ?? collect();
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'isSystemRole' => $role->isSystemRole,
                    'permissions' => $permissions->pluck('name'),
                    'users_count' => $role->users->count(),
                    'created_at' => $role->created_at,
                ];
            });

        return response()->json($roles);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'isSystemRole' => false,
        ]);

        return response()->json([
            'message' => 'Role created successfully.',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'isSystemRole' => $role->isSystemRole,
                'permissions' => [],
                'users_count' => 0,
            ],
        ], 201);
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('permissions', 'users');

        $permissions = $role->getRelation('permissions') ?? collect();

        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'isSystemRole' => $role->isSystemRole,
            'permissions' => $permissions->pluck('name'),
            'users_count' => $role->users->count(),
            'created_at' => $role->created_at,
        ]);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if ($role->isSystemRole) {
            return response()->json([
                'message' => 'System roles cannot be modified.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $role->update($validated);

        return response()->json([
            'message' => 'Role updated successfully.',
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'isSystemRole' => $role->isSystemRole,
            ],
        ]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->isSystemRole) {
            return response()->json([
                'message' => 'System roles cannot be deleted.',
            ], 403);
        }

        $role->users()->detach();
        $role->permissions()->detach();
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }

    public function assignRole(Request $request, Role $role): JsonResponse
    {
        if ($role->isSystemRole) {
            return response()->json([
                'message' => 'Cannot assign system roles.',
            ], 403);
        }

        $validated = $request->validate([
            'userId' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['userId']);
        $user->roles()->syncWithoutDetaching($role->id);

        return response()->json([
            'message' => 'Role assigned successfully.',
            'role' => $role->name,
            'user' => $user->email,
        ]);
    }

    public function removeRole(Request $request, Role $role): JsonResponse
    {
        if ($role->isSystemRole) {
            return response()->json([
                'message' => 'Cannot remove system role assignments.',
            ], 403);
        }

        $validated = $request->validate([
            'userId' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $user = User::findOrFail($validated['userId']);
        $user->roles()->detach($role->id);

        return response()->json([
            'message' => 'Role removed successfully.',
            'role' => $role->name,
            'user' => $user->email,
        ]);
    }
}
