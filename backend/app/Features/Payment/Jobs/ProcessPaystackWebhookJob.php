<?php

namespace App\Features\Payment\Jobs;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Payment\Services\PaystackService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPaystackWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload)
    {
        $this->onQueue(config('queue.payment_webhook_queue', 'default'));
    }

    public function handle(PaystackService $paystack): void
    {
        $reference = (string) data_get($this->payload, 'data.reference', '');
        if ($reference === '') {
            Log::warning('ProcessPaystackWebhookJob skipped - missing transaction reference');
            return;
        }

        $payment = Payment::query()->where('gateway_reference', $reference)->first();
        if (! $payment) {
            Log::warning('ProcessPaystackWebhookJob skipped - payment not found', ['reference' => $reference]);
            return;
        }

        try {
            $verification = $paystack->verifyTransaction($reference);
        } catch (\Throwable $e) {
            Log::error('ProcessPaystackWebhookJob verification failed: ' . $e->getMessage(), ['reference' => $reference]);
            return;
        }

        $isSuccessful = (data_get($verification, 'status') === 'success')
            || (data_get($verification, 'data.status') === 'success');

        $payment->update([
            'status' => $isSuccessful ? 'success' : 'failed',
            'gateway_response' => $verification,
        ]);

        Order::query()->whereKey($payment->order_id)->update([
            'status' => $isSuccessful ? 'completed' : 'failed',
        ]);

        if ($isSuccessful) {
            SendPaymentConfirmationJob::dispatch($payment->order_id);
        }
    }
}
