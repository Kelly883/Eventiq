<?php

namespace Tests\Feature;

use App\Features\Payment\Enums\PaymentGateway;
use App\Features\Payment\Enums\PaymentStatus;
use App\Features\Payment\Jobs\ProcessFlutterwaveWebhookJob;
use App\Features\Payment\Jobs\ProcessPaystackWebhookJob;
use App\Features\Payment\Services\PaymentService;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class PaymentCallbackTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['description' => $roleName]);
        $user = User::factory()->create();
        $user->roles()->attach($role);
        return $user;
    }

    private function makeOrganizerUser(): User
    {
        $user = $this->makeUserWithRole('organizer');
        Organizer::factory()->create(['user_id' => $user->id]);
        return $user;
    }

    // ------------------------------------------------------------------
    // Paystack callback
    // ------------------------------------------------------------------

    public function test_paystack_callback_redirects_when_reference_is_missing(): void
    {
        $this->get('/api/payments/paystack/callback')
            ->assertRedirect();
    }

    public function test_paystack_callback_marks_successful_payment_when_amount_and_currency_match(): void
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
            'reference' => 'ref_success_match',
            'gateway_reference' => 'ref_success_match',
            'amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'pending',
        ]);

        $this->mock(\App\Features\Payment\Services\PaystackService::class, function ($mock) {
            $mock->shouldReceive('verifyTransaction')
                ->with('ref_success_match')
                ->andReturn([
                    'data' => [
                        'reference' => 'ref_success_match',
                        'amount' => 500000,
                        'currency' => 'NGN',
                        'status' => 'success',
                    ],
                ]);
        });

        $this->get('/api/payments/paystack/callback?reference=ref_success_match')
            ->assertRedirect();

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS->value, $payment->status->value);
    }

    public function test_paystack_callback_marks_failed_when_amount_does_not_match(): void
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
            'reference' => 'ref_amount_mismatch',
            'gateway_reference' => 'ref_amount_mismatch',
            'amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'pending',
        ]);

        $this->mock(\App\Features\Payment\Services\PaystackService::class, function ($mock) {
            $mock->shouldReceive('verifyTransaction')
                ->andReturn([
                    'data' => [
                        'reference' => 'ref_amount_mismatch',
                        'amount' => 50000,
                        'currency' => 'NGN',
                        'status' => 'success',
                    ],
                ]);
        });

        $this->get('/api/payments/paystack/callback?reference=ref_amount_mismatch')
            ->assertRedirect();

        $payment->refresh();
        $this->assertSame(PaymentStatus::FAILED->value, $payment->status->value);
    }

    // ------------------------------------------------------------------
    // Flutterwave callback
    // ------------------------------------------------------------------

    public function test_flutterwave_callback_redirects_when_reference_is_missing(): void
    {
        $this->get('/api/payments/flutterwave/callback')
            ->assertRedirect();
    }

    public function test_flutterwave_callback_marks_successful_payment_when_all_fields_match(): void
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
            'reference' => 'flw_ref_match',
            'gateway_reference' => 'flw_ref_match',
            'amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'pending',
        ]);

        $this->mock(\App\Features\Payment\Services\FlutterwaveService::class, function ($mock) {
            $mock->shouldReceive('verifyTransaction')
                ->andReturn([
                    'data' => [
                        'id' => 'flw_123',
                        'tx_ref' => 'flw_ref_match',
                        'amount' => 5000.00,
                        'currency' => 'NGN',
                        'status' => 'successful',
                    ],
                ]);
        });

        $this->get('/api/payments/flutterwave/callback?transaction_id=flw_123&tx_ref=flw_ref_match')
            ->assertRedirect();

        $payment->refresh();
        $this->assertSame(PaymentStatus::SUCCESS->value, $payment->status->value);
    }

    public function test_flutterwave_callback_marks_failed_when_tx_ref_does_not_match(): void
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
            'reference' => 'flw_ref_mismatch',
            'gateway_reference' => 'flw_ref_mismatch',
            'amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'pending',
        ]);

        $this->mock(\App\Features\Payment\Services\FlutterwaveService::class, function ($mock) {
            $mock->shouldReceive('verifyTransaction')
                ->andReturn([
                    'data' => [
                        'id' => 'flw_456',
                        'tx_ref' => 'flw_ref_different',
                        'amount' => 5000.00,
                        'currency' => 'NGN',
                        'status' => 'successful',
                    ],
                ]);
        });

        $this->get('/api/payments/flutterwave/callback?transaction_id=flw_456&tx_ref=flw_ref_mismatch')
            ->assertRedirect();

        $payment->refresh();
        $this->assertSame(PaymentStatus::FAILED->value, $payment->status->value);
    }

    // ------------------------------------------------------------------
    // PaymentService facade
    // ------------------------------------------------------------------

    public function test_payment_service_delegates_initialize_to_gateway(): void
    {
        $mock = \Mockery::mock(\App\Features\Payment\Services\PaystackService::class);
        $mock->shouldReceive('initializeTransaction')
            ->andReturn(['status' => true, 'data' => ['reference' => 'ref123']]);

        $service = new PaymentService(['paystack' => $mock]);

        $result = $service->initialize('paystack', [
            'email' => 'test@example.com',
            'amount' => 5000,
            'reference' => 'ref123',
        ]);

        $this->assertSame(['status' => true, 'data' => ['reference' => 'ref123']], $result);
    }

    public function test_payment_service_throws_for_unsupported_gateway(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $service = new PaymentService([]);
        $service->initialize('stripe', []);
    }

    public function test_payment_service_confirm_payment_fails_on_amount_mismatch(): void
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
            'reference' => 'ref_svc_mismatch',
            'gateway_reference' => 'ref_svc_mismatch',
            'amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'pending',
        ]);

        $mock = \Mockery::mock(\App\Features\Payment\Services\PaystackService::class);
        $mock->shouldReceive('verifyTransaction')
            ->andReturn([
                'data' => [
                    'reference' => 'ref_svc_mismatch',
                    'amount' => 50000,
                    'currency' => 'NGN',
                    'status' => 'success',
                ],
            ]);

        $service = new PaymentService(['paystack' => $mock]);
        $result = $service->confirmPayment('paystack', 'ref_svc_mismatch', $order, $payment);

        $this->assertSame(PaymentStatus::FAILED->value, $result->status->value);
        $this->assertSame('failed', $order->refresh()->status);
    }
}
