<?php

namespace App\Http\Middleware;

use App\Models\Session;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateSessionToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $rawToken = $request->bearerToken();

        if (! is_string($rawToken) || trim($rawToken) === '') {
            return response()->json([
                'message' => 'Unauthenticated. Missing session token.',
            ], 401);
        }

        $session = Session::query()
            ->where('token', $rawToken)
            ->first();

        if (! $session || ! $session->isActive() || ! $session->user) {
            return response()->json([
                'message' => 'Unauthenticated. Invalid, expired, or revoked session.',
            ], 401);
        }

        $request->setUserResolver(fn () => $session->user);

        return $next($request);
    }
}
