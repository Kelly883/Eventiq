<?php

namespace App\Http\Controllers;

use App\Features\PushNotifications\Models\PushNotificationDevice;
use App\Features\OfflineSync\Services\OfflineSyncEngine;
use App\Models\PasswordResetToken;
use App\Models\Session;
use App\Models\User;
use App\Notifications\ResetPassword as ResetPasswordNotification;
use App\Services\CaptchaService;
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
            'captcha_token' => ['nullable', 'string'],
        ]);

        // CAPTCHA after 2 failures in 15m (per IP or per email) when enabled.
        // Frontend should show Turnstile widget when it receives 428 or
        // when /auth/login returns `captcha_required: true`.
        $ip = $request->ip();
        $emailForCaptcha = strtolower((string) ($validated['email'] ?? ''));
        if (CaptchaService::shouldChallenge($ip, $emailForCaptcha)) {
            $token = $validated['captcha_token'] ?? $request->input('captcha_token');
            if (!CaptchaService::verify($token, $ip)) {
                return response()->json([
                    'message' => 'Captcha verification required',
                    'captcha_required' => true,
                ], 428);
            }
        }

        $user = User::where('email', $validated['email'])->first();

        // Mitigate timing-based email enumeration: always perform a bcrypt
        // verification even when the user does not exist, so response time is
        // uniform (~400ms) whether the email is unknown or the password is wrong.
        // The dummy hash is a valid bcrypt hash with cost 12, never matches any input.
        $dummyHash = '$2y$12$Mi6thFWFFYdofMs3jpA8PuRAekPRX3ywiZsv/27opJnbbTprjLnh2';
        $hashToCheck = $user ? $user->passwordHash : $dummyHash;
        $passwordValid = Hash::check($validated['password'], $hashToCheck);

        if (!$user || !$passwordValid) {
            // Record failure for CAPTCHA + throttle. Do not clear on non-existent user
            // to avoid leaking, but still count per-IP to slow enumeration.
            CaptchaService::recordFailure($ip, $emailForCaptcha);
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        // Success → clear failure counters so legitimate user is not challenged.
        CaptchaService::clearFailures($ip, $emailForCaptcha);

        // Prevent suspended/disabled accounts from obtaining new sessions.
        // Keep the same generic 401 message to avoid revealing account status,
        // but distinguish with 403 if caller is auditing; we choose 403 with
        // a neutral message for compliance and monitoring.
        if (($user->status ?? 'active') !== 'active') {
            return response()->json(['message' => 'Account is not active'], 403);
        }

        $plainToken = Str::random(64);

        // Cap active sessions per user to prevent unbounded growth / DoS.
        // Keep the 5 most recent active sessions, revoke the oldest excess.
        $activeCount = $user->sessions()->whereNull('revokedAt')->where('expiresAt', '>', now())->count();
        if ($activeCount >= 5) {
            $excess = $activeCount - 4; // make room for the new one
            $oldestIds = $user->sessions()
                ->whereNull('revokedAt')
                ->where('expiresAt', '>', now())
                ->orderBy('createdAt')
                ->limit($excess)
                ->pluck('id');
            $user->sessions()->whereIn('id', $oldestIds)->update(['revokedAt' => now()]);
        }

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

        // Use a DB unique constraint as the source of truth to avoid a race
        // where two concurrent requests both pass the exists() check.
        // We keep the fast pre-check for the common case (nice 409), but
        // catch a duplicate-key exception for the race condition.
        if (User::where('email', $validated['email'])->exists()) {
            return response()->json(['message' => 'This email is already registered'], 409);
        }

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'passwordHash' => Hash::make($validated['password']),
                'role' => 'attendee',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // SQLSTATE 23000 = integrity constraint violation (unique index)
            if (str_contains($e->getMessage(), 'users_email_unique') || $e->getCode() === '23000') {
                return response()->json(['message' => 'This email is already registered'], 409);
            }
            throw $e;
        }

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
                'token' => Hash::make($plainToken),
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
            'token' => ['required', 'string', 'max:256'],
            'newPassword' => ['required', 'string', 'min:8'],
        ]);

        $plainToken = $validated['token'];
        $tokenHash = hash('sha256', $plainToken);

        // Atomic transaction: mark token used, rotate password, and revoke
        // sessions together. If any step fails the token remains unused.
        $result = DB::transaction(function () use ($plainToken, $tokenHash, $validated) {
            $token = PasswordResetToken::where('token_hash', $tokenHash)
                ->whereNull('usedAt')
                ->where('expiresAt', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $token || ! Hash::check($plainToken, $token->token)) {
                return null;
            }

            $token->update(['usedAt' => now()]);

            $user = $token->user;
            // Defensive: token may point to a deleted user
            if (!$user) {
                return null;
            }

            $user->update([
                'passwordHash' => Hash::make($validated['newPassword']),
                'password_changed_at' => now(),
            ]);
            $user->invalidateAllSessions();
            // Also revoke Sanctum personal access tokens (if any)
            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            return $token;
        });

        if (! $result) {
            return response()->json(['message' => 'This link has expired or is invalid'], 400);
        }

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
