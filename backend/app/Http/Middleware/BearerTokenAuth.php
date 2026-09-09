<?php

namespace App\Http\Middleware;

use App\Models\Session;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bearer token authentication for the token-based auth flow.
 *
 * Authenticates a request by EITHER an existing Sanctum session (cookie /
 * personal access token) OR a custom Bearer token issued by the login flow.
 *
 * Custom Bearer tokens are hashed with SHA-256 and looked up in the
 * `sessions` table. A matching, unexpired, non-revoked session authenticates
 * the request and attaches the associated user.
 */
class BearerTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Already authenticated via a Sanctum session or Sanctum token?
        try {
            $sanctumUser = Auth::guard('sanctum')->user();

            if ($sanctumUser) {
                $request->setUserResolver(fn () => $sanctumUser);
                return $next($request);
            }
        } catch (\Throwable $e) {
            // Fall through to the custom Bearer check below.
        }

        // 2. Try the custom Bearer token issued by POST /api/auth/login.
        $header = $request->header('Authorization', '');

        if (str_starts_with($header, 'Bearer ')) {
            $plainToken = substr($header, 7);

            if ($plainToken !== '' && $plainToken !== '0') {
                $session = Session::where('token', hash('sha256', $plainToken))
                    ->whereNull('revokedAt')
                    ->where('expiresAt', '>', now())
                    ->first();

                if ($session) {
                    $user = $session->user;
                    $request->setUserResolver(fn () => $user);
                    $request->attributes->set('auth_session', $session);

                    return $next($request);
                }
            }
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
