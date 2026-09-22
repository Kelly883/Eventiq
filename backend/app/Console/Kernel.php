<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('tickets:expire-qr-codes')->daily();
        $schedule->command('push:prune-inactive')->daily();
        $schedule->command('auth:prune-expired-tokens --days=7')->daily();
        
        // Expire pending orders older than configured threshold (default 1 hour)
        $schedule->job(new \App\Jobs\ExpirePendingOrders())
            ->everyFiveMinutes()
            ->name('checkout:expire-pending-orders');

        // Payment reconciliation - runs every 6 hours
        $schedule->command('payments:reconcile --hours=6')
            ->everySixHours()
            ->name('payments:reconcile')
            ->withoutOverlapping();

        // Refund reconciliation - runs every 12 hours
        $schedule->command('refunds:reconcile --hours=12')
            ->twiceDaily()
            ->name('refunds:reconcile')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
