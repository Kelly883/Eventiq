<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class ApiIntegrationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api-keys', function (Request $request) {
            $identifier = $request->attributes->get('api_key')?->key_prefix
                ?? $request->ip()
                ?? 'unknown';

            return Limit::perMinute((int) config('api-keys.rate_limit_per_minute', 120))->by($identifier);
        });
    }
}
