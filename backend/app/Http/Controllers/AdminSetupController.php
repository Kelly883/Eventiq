<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AdminSetupController extends Controller
{
    /**
     * GET /api/auth/admin-setup
     *
     * Returns whether the platform still needs initial admin bootstrap.
     * Always returns 200 with `{ "required": true|false }` so the frontend
     * can decide whether to show the setup page without leaking state via
     * status codes.
     */
    public function index(Request $request): JsonResponse
    {
        $hasAdmin = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })
            ->orWhere('role', 'admin')
            ->exists();

        return response()->json([
            'required' => ! $hasAdmin,
        ]);
    }

    /**
     * POST /api/auth/admin-setup
     *
     * One-time bootstrap endpoint: creates the first platform administrator.
     * After any admin exists, this endpoint always returns 404 to prevent
     * discovery/abuse (same generic response regardless of whether an admin
     * already exists).
     */
    public function store(Request $request): JsonResponse
    {
        $limiterKey = 'admin-setup:' . $request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            return response()->json([
                'message' => 'Too many attempts. Please try again later.',
            ], 429);
        }

        RateLimiter::hit($limiterKey, 3600);

        $hasAdmin = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })
            ->orWhere('role', 'admin')
            ->exists();

        if ($hasAdmin) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'string'],
        ]);

        if ($validated['password'] !== $validated['password_confirmation']) {
            return response()->json([
                'message' => 'The password confirmation does not match.',
                'errors' => [
                    'password_confirmation' => ['The password confirmation does not match.'],
                ],
            ], 422);
        }

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator', 'isSystemRole' => true]
        );

        $user = User::unguarded(fn () => User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'passwordHash' => Hash::make($validated['password']),
            'role' => 'admin',
            'emailVerified' => true,
            'status' => 'active',
        ]));

        $user->roles()->syncWithoutDetaching([$adminRole->id]);

        return response()->json([
            'message' => 'Administrator account created successfully. You may now sign in.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }
}
