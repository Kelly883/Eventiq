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

        // Generic auth throttle for register/reset (less strict than login/forgot).
        // Bumped from 5 to 10/min to avoid false 429s during normal user flows
        // (register→login→forgot→reset) on file cache in production.
        RateLimiter::for('auth', function ($request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Login: 5 attempts per 15 minutes per IP + 10 per 15m per email.
        // The per-email limit prevents distributed brute-force (botnet rotating
        // IPs against a single account). Both must pass (array).
        RateLimiter::for('login', function ($request) {
            $email = strtolower((string) $request->input('email', ''));
            $emailKey = $email !== '' ? 'login_email_' . sha1($email) : 'login_email_unknown';
            return [
                Limit::perMinutes(15, 5)->by($request->ip()),
                Limit::perMinutes(15, 10)->by($emailKey),
            ];
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

        // Admin user/role/permission management — 10/min per admin (strict)
        RateLimiter::for('admin-users', function ($request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('admin-roles-assign', function ($request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('admin-permissions', function ($request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('admin-audit-log', function ($request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        Event::observe(EventObserver::class);
        Ticket::observe(TicketObserver::class);
    }
}
