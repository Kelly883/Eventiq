<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;

class SetupController extends Controller
{
    /**
     * Show a minimal JSON endpoint for creating the first admin.
     * Only accessible when no admin exists and the config flag is true.
     */
    public function showForm()
    {
        if (! Config::get('setup.allow_first_admin')) {
            abort(403, 'First‑admin setup is disabled.');
        }

        $adminExists = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->exists();
        if ($adminExists) {
            abort(403, 'Admin account already created.');
        }

        // In a real UI you would return a Blade view. For API‑first we just return a schema.
        return response()->json([
            'message' => 'Provide name, email, password, password_confirmation to create the first admin.',
            'fields' => ['name', 'email', 'password', 'password_confirmation'],
        ]);
    }

    /**
     * Store the first admin.
     */
    public function store(Request $request)
    {
        if (! Config::get('setup.allow_first_admin')) {
            abort(403, 'First‑admin setup is disabled.');
        }

        $adminExists = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))->exists();
        if ($adminExists) {
            return response()->json(['message' => 'Admin already exists'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator', 'isSystemRole' => true]
        );

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'passwordHash' => Hash::make($validated['password']),
            'role' => 'admin',
            'role_id' => $adminRole->id,
            'emailVerified' => true,
            'status' => 'active',
        ]);

        $user->roles()->syncWithoutDetaching([$adminRole->id]);

        return response()->json([
            'message' => 'First admin created successfully.',
            'user' => $user->only(['id', 'name', 'email']),
        ], 201);
    }
}
