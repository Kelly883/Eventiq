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

        // Login: 5 attempts per 15 minutes per IP (brute-force protection).
        RateLimiter::for('login', function ($request) {
            return Limit::perMinutes(15, 5)->by($request->ip());
        });

        // Forgot-password: 3 requests per hour per IP (abuse prevention), plus
        // 5 requests per hour per email to slow distributed attacks that
        // rotate IPs while targeting a single address. Returning an array of
        // Limit objects applies every constraint (all must pass).
        RateLimiter::for('forgot-password', function ($request) {
            $email = (string) $request->input('email', '');
            $emailKey = $email !== '' ? 'email_' . sha1(strtolower($email)) : 'email_unknown';

            return [
                Limit::perMinutes(60, 3)->by($request->ip()),
                Limit::perMinutes(60, 5)->by($emailKey),
            ];
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
