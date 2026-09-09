<?php

namespace App\Http\Controllers;

use App\Features\PushNotifications\Models\PushNotificationDevice;
use App\Features\OfflineSync\Services\OfflineSyncEngine;
use App\Models\PasswordResetToken;
use App\Models\Session;
use App\Models\User;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     *
     * Validates credentials, creates a Session record with a 7-day expiry,
     * updates lastLoginAt, and returns a plain token plus user profile.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        // Constant-time comparison; same message whether email or password is wrong
        // so the response never reveals which field was incorrect.
        if (!$user || !Hash::check($validated['password'], $user->passwordHash)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        $plainToken = Str::random(64);

        Session::create([
            'userId' => $user->id,
            'token' => hash('sha256', $plainToken),
            'expiresAt' => now()->addDays(7),
        ]);

        $user->update(['lastLoginAt' => now()]);

        return response()->json([
            'token' => $plainToken,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * POST /api/auth/register
     *
     * Validates input, rejects duplicate email with 409, hashes the password
     * with bcrypt (cost 12), and creates an attendee. Returns 201 with the
     * public user fields only — no token, no password hash.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if (User::where('email', $validated['email'])->exists()) {
            return response()->json(['message' => 'This email is already registered'], 409);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'passwordHash' => Hash::make($validated['password'], ['rounds' => 12]),
            'role' => 'attendee',
        ]);

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
        ], 201);
    }

    /**
     * POST /api/auth/forgot-password
     *
     * Always returns 200 with the same generic message (prevents email
     * enumeration). When the email exists, generates a random token, stores
     * a bcrypt hash of it with a 1-hour expiry, and sends the reset email.
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            $plainToken = Str::random(64);

            PasswordResetToken::create([
                'userId' => $user->id,
                'token' => Hash::make($plainToken, ['rounds' => 12]),
                'token_hash' => hash('sha256', $plainToken),
                'expiresAt' => now()->addHour(),
            ]);

            $user->notify(new ResetPasswordNotification($plainToken));
        }

        return response()->json([
            'message' => 'If an account exists, a reset link has been sent',
        ]);
    }

    /**
     * POST /api/auth/reset-password
     *
     * Validates the token, checks expiry and prior use, hashes the new
     * password, marks the token used, and invalidates all existing sessions.
     *
     * Uses a database transaction with row-level locking (lockForUpdate) to
     * prevent race conditions where two concurrent requests could both
     * consume the same valid token.
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'newPassword' => ['required', 'string', 'min:8'],
        ]);

        $plainToken = $validated['token'];
        $tokenHash = hash('sha256', $plainToken);

        // Wrap in a transaction with row locking to prevent concurrent reuse.
        $resetToken = DB::transaction(function () use ($plainToken, $tokenHash) {
            // O(1) lookup via the deterministic sha-256 hash, then lock the row.
            $token = PasswordResetToken::where('token_hash', $tokenHash)
                ->whereNull('usedAt')
                ->where('expiresAt', '>', now())
                ->lockForUpdate()
                ->first();

            // Verify against the bcrypt hash before trusting the lookup.
            if (! $token || ! Hash::check($plainToken, $token->token)) {
                return null;
            }

            // Mark as used inside the same transaction so concurrent requests
            // that pass the lock see the updated state.
            $token->update(['usedAt' => now()]);

            return $token;
        });

        if (! $resetToken) {
            return response()->json(['message' => 'This link has expired or is invalid'], 400);
        }

        $user = $resetToken->user;

        $user->update(['passwordHash' => Hash::make($validated['newPassword'], ['rounds' => 12])]);
        $user->invalidateAllSessions();

        return response()->json(['message' => 'Password reset successfully']);
    }

    /**
     * GET /api/auth/me — returns the authenticated user.
     * Supports both Sanctum sessions and Bearer token auth.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($user->load('roles'));
    }

    /**
     * POST /api/auth/logout — logs out, purges device tokens and offline queue.
     * Revokes the current Bearer session when present.
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $tokens = PushNotificationDevice::where('user_id', $user->id)->pluck('token')->all();
            PushNotificationDevice::where('user_id', $user->id)->delete();

            // The device token rows are gone, so any offline operations they
            // had queued are orphaned too — purge them.
            (new OfflineSyncEngine())->purgeDeviceOperations($tokens);

            // Revoke the current Bearer session if one was used.
            if ($request->attributes->has('auth_session')) {
                $request->attributes->get('auth_session')->update(['revokedAt' => now()]);
            }
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
