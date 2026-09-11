<?php

namespace App\Http\Middleware;

use App\Models\Session;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OptionalBearer
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $sanctumUser = Auth::guard('sanctum')->user();
            if ($sanctumUser) {
                $request->setUserResolver(fn () => $sanctumUser);
                return $next($request);
            }
        } catch (\Throwable $e) {
        }

        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            $plainToken = substr($header, 7);
            if ($plainToken === '' || $plainToken === '0' || strlen($plainToken) > 256 || strlen($plainToken) < 32) {
                // Invalid token format -> treat as unauthenticated, don't leak 401 for public endpoints
                return $next($request);
            }
            $session = Session::where('token', hash('sha256', $plainToken))
                ->whereNull('revokedAt')
                ->where('expiresAt', '>', now())
                ->first();
            if ($session) {
                $user = $session->user;
                if ($user && $user->password_changed_at && $session->createdAt) {
                    if ($session->createdAt->lt($user->password_changed_at)) {
                        return $next($request);
                    }
                }
                if ($user) {
                    $request->setUserResolver(fn () => $user);
                    $request->attributes->set('auth_session', $session);
                }
            }
        }
        return $next($request);
    }
}
