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
        ]);

        $middleware->alias([
            'role' => App\Http\Middleware\CheckRole::class,
            'api.key' => App\Http\Middleware\ApiKeyMiddleware::class,
            'session.auth' => App\Http\Middleware\ValidateSessionToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withProviders([
        GraphQLServiceProvider::class,
    ])
    ->create();
