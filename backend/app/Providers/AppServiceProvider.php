<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Models\Event;
use App\Models\Organizer;
use App\Features\Checkout\Models\Ticket;
use App\Observers\EventObserver;
use App\Observers\TicketObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('admin', function ($request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function ($request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // event-ticketing-prd-export: EventBrowsePage and CategoryBrowsePage
        // both explicitly specify "Rate limit 30/min per IP to prevent
        // scraping" under SECURITY. Always by IP specifically, not
        // $request->user()?->id -- these are genuinely public,
        // unauthenticated endpoints (auth:sanctum isn't applied to them at
        // all), so there's never a user to key on in the first place.
        RateLimiter::for('discovery', function ($request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        Event::observe(EventObserver::class);
        Ticket::observe(TicketObserver::class);
    }
}
