<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * IsAdmin middleware — ensures the authenticated user has the admin role.
 * Used alongside `auth:sanctum` to protect admin-only endpoints.
 * Also compatible with Laravel Policies (AdminPolicy) for fine-grained checks.
 */
class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Use the canonical hasRole('admin') check — covers both role_id and roles pivot
        if (!method_exists($user, 'isAdmin') || !$user->isAdmin()) {
            // Fallback to hasRole if isAdmin not available
            $isAdmin = method_exists($user, 'hasRole') ? $user->hasRole('admin') : false;
            if (!$isAdmin) {
                return response()->json(['message' => 'Forbidden — admin access required'], 403);
            }
        }

        return $next($request);
    }
}
