<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reset the auth guard state at the start of every API request.
 *
 * The framework's auth guards cache their resolved user
 * (Illuminate\Auth\SessionGuard::user()) and the Auth manager caches guard
 * instances for the whole process (AuthManager::guard()), with NO reset at
 * the request boundary. In a long-lived process (php artisan serve on Render,
 * the PHPUnit suite) a user set by BearerTokenAuth via Auth::setUser() in one
 * request would otherwise leak into the NEXT request:
 *
 *   - an unauthenticated caller is treated as the previous request's user
 *     (authentication bypass / cross-user data exposure);
 *   - logout revocations appear ineffective — /auth/me still returns 200
 *     because the guard keeps answering with the cached user instead of
 *     reading the revoked Session row.
 *
 * Clearing the guard cache per-request restores request isolation while
 * keeping Auth::user() available to controllers for the CURRENT request
 * (DeveloperController, OrganizerPayoutController rely on it for the
 * bearer-authenticated user).
 *
 * Registered with $middleware->api(prepend: ...) so it runs before Sanctum's
 * EnsureFrontendRequestsAreStateful / StartSession middleware.
 */
class ResetAuthState
{
    public function handle(Request $request, Closure $next): Response
    {
        Auth::forgetGuards();

        return $next($request);
    }
}