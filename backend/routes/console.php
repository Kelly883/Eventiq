<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('audit:prune')->dailyAt('02:15')->withoutOverlapping();

// Prune expired password reset tokens. Keep 7 days after expiry for audit,
// then delete. Without this the table grows unbounded (one row per forgot).
// Matches the `DELETE FROM password_reset_tokens WHERE expiresAt < NOW()-7d`.
Schedule::call(function () {
    \App\Models\PasswordResetToken::where('expiresAt', '<', now()->subDays(7))->delete();
})->dailyAt('03:00')->name('prune:password-reset-tokens')->withoutOverlapping();

// Also prune orphaned expired active tokens daily at 03:05 (1h expiry + 7d retention)
// to keep the idx_prt_hash_used_expires index lean. This is idempotent.
Schedule::call(function () {
    // Delete tokens that are both expired and used (already consumed) older than 1 day
    // to keep table small, but keep unused expired for 7d for forensics.
    \App\Models\PasswordResetToken::where('expiresAt', '<', now()->subDay())
        ->whereNotNull('usedAt')
        ->delete();
})->dailyAt('03:05')->name('prune:used-reset-tokens')->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Ticketing / checkout maintenance
|--------------------------------------------------------------------------
| These MUST be registered here and not in app/Console/Kernel.php.
| This app runs Laravel 11 (v11.55.1), whose console kernel is
| Illuminate\Foundation\Console\Kernel; the framework never resolves
| App\Console\Kernel, so anything scheduled in its schedule() method is
| silently dropped. Verify with `php artisan schedule:list`.
*/

// Invalidate expired QR codes so stale scans cannot be replayed forever.
Schedule::command('tickets:expire-qr-codes')->daily();

// Drop push devices that have not been used recently (keeps fan-out cheap).
Schedule::command('push:prune-inactive')->daily();

// NOTE: `auth:prune-expired-tokens --days=7` is intentionally NOT scheduled.
// It deletes exactly the same rows (PasswordResetToken where expiresAt < now
// -7d) as the `prune:password-reset-tokens` closure above, so running both
// would double the work for no benefit. The command remains available for
// manual/backfill runs.

// Expire pending orders older than the configured threshold (default 1 hour).
// Without this, abandoned checkouts hold reserved inventory indefinitely.
Schedule::job(new \App\Jobs\ExpirePendingOrders())
    ->everyFiveMinutes()
    ->name('checkout:expire-pending-orders')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Payment / refund reconciliation
|--------------------------------------------------------------------------
| Safety net for webhook deliveries that were missed, rejected, or that
| arrived before the order existed locally. Both commands are idempotent -
| they never issue tickets for an order that already has them.
*/

// Detect payments that succeeded at the gateway but never reconciled locally.
Schedule::command('payments:reconcile --hours=6')
    ->everySixHours()
    ->name('payments:reconcile')
    ->withoutOverlapping();

// Detect refunds issued at the gateway that have no local refund record.
Schedule::command('refunds:reconcile --hours=12')
    ->twiceDaily()
    ->name('refunds:reconcile')
    ->withoutOverlapping();
