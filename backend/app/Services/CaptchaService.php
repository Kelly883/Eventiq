<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies Cloudflare Turnstile (or hCaptcha-compatible) tokens.
 * Disabled when TURNSTILE_ENABLED=false (local dev / tests).
 */
class CaptchaService
{
    public static function isEnabled(): bool
    {
        return filter_var(env('TURNSTILE_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Returns true if the request should be challenged (≥2 failures in last 15m).
     */
    public static function shouldChallenge(string $ip, string $email): bool
    {
        if (! self::isEnabled()) {
            return false;
        }

        $ipFails = (int) \Illuminate\Support\Facades\Cache::get('login_fail_ip:' . $ip, 0);
        $emailFails = 0;
        if ($email !== '') {
            $emailFails = (int) \Illuminate\Support\Facades\Cache::get('login_fail_email:' . sha1(strtolower($email)), 0);
        }

        return $ipFails >= 2 || $emailFails >= 2;
    }

    /**
     * Record a failed login for IP + email (15m window, matches login throttle).
     */
    public static function recordFailure(string $ip, string $email): void
    {
        $ttl = 15 * 60;
        \Illuminate\Support\Facades\Cache::put(
            'login_fail_ip:' . $ip,
            (int) \Illuminate\Support\Facades\Cache::get('login_fail_ip:' . $ip, 0) + 1,
            $ttl
        );
        if ($email !== '') {
            $key = 'login_fail_email:' . sha1(strtolower($email));
            \Illuminate\Support\Facades\Cache::put($key, (int) \Illuminate\Support\Facades\Cache::get($key, 0) + 1, $ttl);
        }
    }

    public static function clearFailures(string $ip, string $email): void
    {
        \Illuminate\Support\Facades\Cache::forget('login_fail_ip:' . $ip);
        if ($email !== '') {
            \Illuminate\Support\Facades\Cache::forget('login_fail_email:' . sha1(strtolower($email)));
        }
    }

    /**
     * Verify the Turnstile token with Cloudflare. Returns true on success.
     * When disabled, always true. When secret missing, logs and returns false.
     */
    public static function verify(?string $token, string $ip): bool
    {
        if (! self::isEnabled()) {
            return true;
        }

        $secret = env('TURNSTILE_SECRET_KEY') ?: config('services.turnstile.secret');
        if (!$secret) {
            Log::warning('TURNSTILE_ENABLED=true but no secret configured');
            return false;
        }

        if (!$token) {
            return false;
        }

        try {
            $resp = Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]);

            return (bool) ($resp->json('success') ?? false);
        } catch (\Throwable $e) {
            Log::error('Turnstile verify failed', ['error' => $e->getMessage()]);
            // Fail closed when enabled
            return false;
        }
    }
}
