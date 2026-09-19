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
 *
 * IDLE TIMEOUT: Sessions are invalidated after SESSION_IDLE_TIMEOUT_MINUTES
 * (default 30) of inactivity. Each authenticated request updates lastActivityAt.
 */
class BearerTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Already authenticated via a Sanctum session or Sanctum token?
        try {
            $sanctumUser = Auth::guard('sanctum')->user();

            if ($sanctumUser) {
                // CRITICAL: Even if Sanctum authenticated, check for idle timeout
                // on the custom session. This prevents Sanctum from bypassing
                // the idle timeout when both auth mechanisms are active.
                $header = $request->header('Authorization', '');
                if (str_starts_with($header, 'Bearer ')) {
                    $plainToken = substr($header, 7);
                    $session = Session::where('token', hash('sha256', $plainToken))
                        ->whereNull('revokedAt')
                        ->first();

                    if ($session) {
                        // Check idle timeout — Sanctum must NOT bypass this
                        if ($session->isIdleExpired()) {
                            $session->revoke();
                            return response()->json(['message' => 'Session expired due to inactivity'], 401);
                        }
                        // Check absolute expiration
                        if (!$session->expiresAt->isFuture()) {
                            $session->revoke();
                            return response()->json(['message' => 'Session expired'], 401);
                        }
                        // Record activity and continue
                        $session->recordActivity();
                    }
                }

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

            // Reject abnormally long tokens before hashing to avoid DoS
            // (10MB token → hash + DB lookup). Legit tokens are 64 chars.
            if ($plainToken === '' || $plainToken === '0' || strlen($plainToken) > 256) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            // Avoid logging the plain token; hash is safe to query.
            if (strlen($plainToken) < 32) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $session = Session::where('token', hash('sha256', $plainToken))
                ->whereNull('revokedAt')
                ->first();

            if ($session) {
                // Check absolute expiration
                if (!$session->expiresAt->isFuture()) {
                    $session->revoke();
                    return response()->json(['message' => 'Session expired'], 401);
                }

                // Check idle timeout
                if ($session->isIdleExpired()) {
                    $session->revoke();
                    return response()->json(['message' => 'Session expired due to inactivity'], 401);
                }

                $user = $session->user;
                // Concurrent session invalidation defense: if password was changed
                // after this session was created, reject even if not yet revoked
                // (covers race where login creates session after invalidateAllSessions).
                if ($user && $user->password_changed_at && $session->createdAt) {
                    if ($session->createdAt->lt($user->password_changed_at)) {
                        return response()->json(['message' => 'Unauthorized'], 401);
                    }
                }

                // Record activity on successful auth (sliding expiration)
                $session->recordActivity();

                $request->setUserResolver(fn () => $user);
                $request->attributes->set('auth_session', $session);

                return $next($request);
            }
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
