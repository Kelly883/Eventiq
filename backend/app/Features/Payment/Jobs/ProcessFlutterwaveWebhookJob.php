<?php

namespace App\Features\Payment\Jobs;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Payment\Services\FlutterwaveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFlutterwaveWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload)
    {
        $this->onQueue(config('queue.payment_webhook_queue', 'default'));
    }

    public function handle(FlutterwaveService $flutterwave): void
    {
        $reference = (string) (data_get($this->payload, 'data.tx_ref')
            ?? data_get($this->payload, 'txRef')
            ?? '');

        if ($reference === '') {
            Log::warning('ProcessFlutterwaveWebhookJob skipped - missing transaction reference');
            return;
        }

        $payment = Payment::query()->where('gateway_reference', $reference)->first();
        if (! $payment) {
            Log::warning('ProcessFlutterwaveWebhookJob skipped - payment not found', ['reference' => $reference]);
            return;
        }

        try {
            $verification = $flutterwave->verifyTransaction($reference);
        } catch (\Throwable $e) {
            Log::error('ProcessFlutterwaveWebhookJob verification failed: ' . $e->getMessage(), ['reference' => $reference]);
            return;
        }

        $isSuccessful = (data_get($verification, 'status') === 'successful')
            || (data_get($verification, 'data.status') === 'successful');

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
