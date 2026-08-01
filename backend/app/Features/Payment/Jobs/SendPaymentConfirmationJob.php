<?php

namespace App\Features\Payment\Jobs;

use App\Features\Checkout\Models\Order;
use App\Services\TicketDeliveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPaymentConfirmationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string|int $orderId)
    {
        $this->onQueue(config('queue.payment_queue', 'default'));
    }

    public function handle(TicketDeliveryService $deliveryService): void
    {
        $order = Order::query()
            ->with(['user:id,email', 'event:id,title'])
            ->find($this->orderId);

        if (! $order || ! $order->user?->email) {
            Log::warning('SendPaymentConfirmationJob skipped - order/user email missing', [
                'order_id' => $this->orderId,
            ]);

            return;
        }

        $deliveryService->send('email', [
            'to' => $order->user->email,
            'subject' => 'Payment confirmed for ' . ($order->event->title ?? 'your event'),
            'body' => 'Your payment has been confirmed and your order is now complete.',
        ]);
    }
}
