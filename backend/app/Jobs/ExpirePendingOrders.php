<?php

namespace App\Jobs;

use App\Features\Checkout\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ExpirePendingOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds to cache the result.
     */
    public int $tries = 3;

    /**
     * Expiration threshold - orders older than this are expired.
     * Configurable via config/app.php as 'pending_order_expiration_hours'.
     */
    public function handle(): void
    {
        $expirationThreshold = now()->subHours(
            config('app.pending_order_expiration_hours', 1)
        );

        $expiredCount = Order::pendingOlderThan($expirationThreshold)
            ->lockForUpdate()
            ->update(['status' => 'expired']);

        if ($expiredCount > 0) {
            Log::info("ExpirePendingOrders: expired {$expiredCount} pending orders older than {$expirationThreshold->toDateTimeString()}");
        }
    }
}