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
