<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Models\Event;
use App\Models\Organizer;
use App\Features\Checkout\Models\Ticket;
use App\Observers\EventObserver;
use App\Observers\OrganizerObserver;
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

        Event::observe(EventObserver::class);
        Organizer::observe(OrganizerObserver::class);
        Ticket::observe(TicketObserver::class);
    }
}
