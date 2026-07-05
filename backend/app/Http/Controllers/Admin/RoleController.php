<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function __construct()
    {
        // All methods require admin privileges
        $this->authorizeResource(Role::class);
    }

    public function index()
    {
        // List all roles with permissions
    }

    public function store(Request $request)
    {
        // Create new role
    }

    public function show(Role $role)
    {
        // Show single role with permissions
    }

    public function update(Request $request, Role $role)
    {
        // Update role
    }

    public function destroy(Role $role)
    {
        // Delete role
    }

    public function assignRole(Request $request)
    {
        // Assign role to user
    }

    public function removeRole(Request $request)
    {
        // Remove role from user
    }
}
