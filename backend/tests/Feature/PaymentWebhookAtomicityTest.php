<?php

namespace Tests\Feature;

use App\Features\Payment\Enums\PaymentGateway;
use App\Features\Payment\Jobs\ProcessFlutterwaveWebhookJob;
use App\Features\Payment\Jobs\ProcessPaystackWebhookJob;
use App\Features\Payment\Models\Transaction;
use App\Features\Payment\Services\FlutterwaveService;
use App\Features\Payment\Services\PaystackService;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PaymentWebhookAtomicityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['description' => $roleName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    public function test_paystack_webhook_job_updates_payment_order_and_transaction_atomically(): void
    {
        $user = $this->makeUserWithRole('attendee');
        $order = \App\Features\Checkout\Models\Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 5000.00,
            'currency' => 'NGN',
        ]);
        $payment = \App\Features\Checkout\Models\Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => PaymentGateway::PAYSTACK->value,
            'reference' => 'ref_txn_paystack',
            'gateway_reference' => 'ref_txn_paystack',
            'amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'pending',
            'webhook_event_id' => null,
        ]);

        $paystack = Mockery::mock(PaystackService::class);
        $paystack->shouldReceive('verifyTransaction')
            ->with('ref_txn_paystack')
            ->andReturn([
                'data' => [
                    'reference' => 'ref_txn_paystack',
                    'status' => 'success',
                    'amount' => 500000,
                    'currency' => 'NGN',
                ],
            ]);

        $job = new ProcessPaystackWebhookJob([
            'event' => 'charge.success',
            'data' => [
                'id' => 'evt_txn_001',
                'reference' => 'ref_txn_paystack',
                'status' => 'success',
                'amount' => 500000,
                'currency' => 'NGN',
            ],
        ]);

        $job->handle($paystack);

        $payment->refresh();
        $order->refresh();

        $this->assertSame('success', $payment->status->value);
        $this->assertSame('completed', $order->status);
        $this->assertSame('evt_txn_001', $payment->webhook_event_id);
        $this->assertNotNull(Transaction::where('reference', 'ref_txn_paystack')->first());
    }

    public function test_flutterwave_webhook_job_updates_payment_order_and_transaction_atomically(): void
    {
        $user = $this->makeUserWithRole('attendee');
        $order = \App\Features\Checkout\Models\Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 5000.00,
            'currency' => 'NGN',
        ]);
        $payment = \App\Features\Checkout\Models\Payment::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'gateway' => PaymentGateway::FLUTTERWAVE->value,
            'reference' => 'ref_txn_flutterwave',
            'gateway_reference' => 'ref_txn_flutterwave',
            'amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'pending',
            'webhook_event_id' => null,
        ]);

        $flutterwave = Mockery::mock(FlutterwaveService::class);
        $flutterwave->shouldReceive('verifyTransaction')
            ->with('ref_txn_flutterwave')
            ->andReturn([
                'data' => [
                    'id' => 'flw_evt_001',
                    'tx_ref' => 'ref_txn_flutterwave',
                    'status' => 'successful',
                    'amount' => 5000.00,
                    'currency' => 'NGN',
                ],
            ]);

        $job = new ProcessFlutterwaveWebhookJob([
            'event' => 'charge.completed',
            'data' => [
                'id' => 'flw_evt_001',
                'tx_ref' => 'ref_txn_flutterwave',
                'status' => 'successful',
                'amount' => 5000.00,
                'currency' => 'NGN',
            ],
        ]);

        $job->handle($flutterwave);

        $payment->refresh();
        $order->refresh();

        $this->assertSame('success', $payment->status->value);
        $this->assertSame('completed', $order->status);
        $this->assertSame('flw_evt_001', $payment->webhook_event_id);
        $this->assertNotNull(Transaction::where('reference', 'ref_txn_flutterwave')->first());
    }
}
