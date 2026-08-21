<?php

namespace App\Features\Payment\Jobs;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Payment\Enums\PaymentGateway;
use App\Features\Payment\Models\Transaction;
use App\Features\Payment\Services\FlutterwaveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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

        $webhookEventId = (string) (data_get($this->payload, 'data.id')
            ?? data_get($this->payload, 'id')
            ?? '');
        if ($webhookEventId !== '' && $payment->webhook_event_id === $webhookEventId) {
            return;
        }

        if (in_array($payment->status, ['success', 'failed', 'cancelled', 'refunded'], true)) {
            return;
        }

        try {
            $verification = $flutterwave->verifyTransaction($reference);
        } catch (\Throwable $e) {
            Log::error('ProcessFlutterwaveWebhookJob verification failed: ' . $e->getMessage(), ['reference' => $reference]);
            return;
        }

        $rawStatus = (string) (data_get($verification, 'data.status', data_get($verification, 'status', '')));

        $status = match ($rawStatus) {
            'successful', 'completed' => 'success',
            'failed' => 'failed',
            'pending' => 'pending',
            default => 'failed',
        };

        $payment->update([
            'status' => $status,
            'gateway_response' => $verification,
            'webhook_event_id' => $webhookEventId,
        ]);

        Transaction::updateOrCreate(
            ['reference' => $reference, 'gateway' => PaymentGateway::FLUTTERWAVE],
            [
                'user_id' => $payment->user_id,
                'organizer_id' => $payment->organizer_id,
                'order_id' => $payment->order_id,
                'event_id' => $payment->event_id,
                'ticket_id' => $payment->ticket_id,
                'gateway_transaction_id' => $payment->gateway_transaction_id,
                'gateway_reference' => $reference,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'status' => $status,
                'payment_channel' => $payment->payment_channel,
                'customer_email' => $payment->customer_email,
                'customer_code' => $payment->customer_code,
                'authorization_code' => $payment->authorization_code,
                'authorization_type' => $payment->authorization_type,
                'fees' => $payment->fees,
                'net_amount' => $payment->net_amount,
                'refunded_amount' => $payment->refunded_amount,
                'refund_reference' => $payment->refund_reference,
                'is_fully_refunded' => $payment->is_fully_refunded,
                'paid_at' => $status === 'success' ? now() : null,
                'last_error' => $status === 'failed' ? $rawStatus : null,
                'gateway_response' => $verification,
                'webhook_event_id' => $webhookEventId,
                'webhook_idempotency_key' => $webhookEventId ?: md5($reference . $status . now()),
                'attempts' => DB::raw('IFNULL(attempts, 0) + 1'),
            ]
        );

        Order::query()->whereKey($payment->order_id)->update([
            'status' => $status === 'success' ? 'completed' : ($status === 'pending' ? 'pending' : 'failed'),
        ]);

        if ($status === 'success') {
            SendPaymentConfirmationJob::dispatch($payment->order_id);
        }
    }
}
