<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Rebing\GraphQL\GraphQLServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust proxies only when explicitly configured. In production behind
        // Cloudflare/ALB/Render, set TRUSTED_PROXIES="*" or CIDRs in .env.
        // When null (default) no X-Forwarded-* is trusted, so rate limits
        // keyed by $request->ip() cannot be bypassed via header spoofing.
        $trustedProxies = env('TRUSTED_PROXIES', null);
        if ($trustedProxies !== null && $trustedProxies !== '') {
            $middleware->trustProxies(
                at: $trustedProxies === '*' ? '*' : array_map('trim', explode(',', (string) $trustedProxies))
            );
        }

        // Global CORS handling so the browser at localhost:3000 can reach the
        // backend at localhost:8000 for *all* API routes. Without this the
        // browser blocks responses that lack Access-Control-Allow-Origin, and
        // Sanctum's stateful cookie auth never gets a chance to run.
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);

        // API consumers must always receive an authentication response, even
        // when their client does not send an Accept: application/json header.
        // Without this, Laravel attempts to redirect guests to a named web
        // "login" route, which this API-only auth flow does not define.
        $middleware->redirectGuestsTo(fn (Request $request) =>
            $request->is('api/*') ? null : route('login')
        );

        $middleware->append(App\Http\Middleware\AssignCorrelationId::class);

        // Enable Sanctum's stateful SPA authentication for the API guard.
        // This allows the frontend to authenticate using secure cookies
        // instead of bearer tokens.
        $middleware->api(append: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->alias([
            'role' => App\Http\Middleware\CheckRole::class,
            'isAdmin' => App\Http\Middleware\IsAdmin::class,
            'api.key' => App\Http\Middleware\ApiKeyMiddleware::class,
            'session.auth' => App\Http\Middleware\ValidateSessionToken::class,
            'bearer' => App\Http\Middleware\BearerTokenAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $e->getMessage()], 401);
            }
        });

        // Never leak stack traces for throttling, even when APP_DEBUG=true
        // (live curl previously dumped full trace). Always return a minimal JSON.
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, $request) {
            if ($request->is('api/*')) {
                // Preserve Retry-After headers from the original exception
                $headers = method_exists($e, 'getHeaders') ? $e->getHeaders() : [];
                return response()->json(['message' => 'Too Many Attempts.'], 429, $headers);
            }
        });

        // Validation: ensure api/* always returns JSON 422 even without Accept header
        // (otherwise Laravel redirects 302 to "/"). Keep 422 to match tests.
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // For any other unhandled exception on api/*, never expose trace in JSON
        // when debug is on; still log it server-side via the default logger.
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') && app()->hasDebugModeEnabled()) {
                // Let Laravel's default handler log, but strip trace from response
                // for 5xx. For 4xx we already handled above.
                if (! $e instanceof \Illuminate\Http\Exceptions\HttpResponseException
                    && ! $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                    // Only intervene for 500-class to avoid double-handling
                    if (method_exists($e, 'getStatusCode') && $e->getStatusCode() < 500) {
                        return null;
                    }
                    return response()->json(['message' => 'Server Error'], 500);
                }
            }
        });
    })
    ->withProviders([
        GraphQLServiceProvider::class,
    ])
    ->create();
