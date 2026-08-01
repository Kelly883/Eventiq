<?php

namespace Tests\Feature;

use App\Features\Payment\Jobs\ProcessFlutterwaveWebhookJob;
use App\Features\Payment\Jobs\ProcessPaystackWebhookJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PaymentWebhookDispatchTest extends TestCase
{
    public function test_paystack_webhook_dispatches_processing_job_when_signature_is_valid(): void
    {
        config()->set('payment.gateways.paystack.secret_key', 'test_paystack_secret');

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => ['reference' => 'ord_test_123'],
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha512', $payload, 'test_paystack_secret');
        Queue::fake();

        $response = $this->withHeaders([
            'x-paystack-signature' => $signature,
            'Content-Type' => 'application/json',
        ])->call('POST', '/api/payments/paystack/webhook', [], [], [], [], $payload);

        $response->assertOk()->assertJson(['received' => true]);
        Queue::assertPushed(ProcessPaystackWebhookJob::class);
    }

    public function test_flutterwave_webhook_dispatches_processing_job_when_signature_is_valid(): void
    {
        config()->set('payment.gateways.flutterwave.secret_key', 'flw_secret_key');
        config()->set('payment.gateways.flutterwave.webhook_secret_hash', 'flw_hash');

        Queue::fake();

        $response = $this->withHeaders([
            'verif-hash' => 'flw_hash',
        ])->postJson('/api/payments/flutterwave/webhook', [
            'data' => ['tx_ref' => 'ord_test_456'],
        ]);

        $response->assertOk()->assertJson(['received' => true]);
        Queue::assertPushed(ProcessFlutterwaveWebhookJob::class);
    }
}
