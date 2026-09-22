<?php

namespace Tests\Feature;

use App\Features\Payment\Services\PayoutService;
use App\Services\PaymentGatewayService;
use App\Features\Fraud\Models\FraudEvent;
use App\Features\Payment\Models\OrganizerPayout;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class PayoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'organizer'): User
    {
        $user = User::factory()->create([
            'emailVerified' => true,
            'created_at' => now()->subDays(30),
        ]);

        if ($role === 'admin') {
            $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator', 'isSystemRole' => true]);
            $user->roles()->attach($adminRole);
        }

        return $user;
    }

    private function makeOrganizer(User $user): Organizer
    {
        return Organizer::create([
            'user_id' => $user->id,
            'displayName' => $user->name,
            'verificationStatus' => 'verified',
            'paystack_connect_status' => 'enabled',
            'fraud_hold' => false,
        ]);
    }

    public function test_payout_blocked_when_organizer_under_fraud_hold(): void
    {
        $user = $this->makeUser();
        $organizer = $this->makeOrganizer($user);
        $organizer->update([
            'fraud_hold' => true,
            'fraud_hold_reason' => 'Suspicious ticket sales pattern detected',
        ]);

        $paymentGatewayService = Mockery::mock(PaymentGatewayService::class);
        $payoutService = new PayoutService($paymentGatewayService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('fraud investigation');

        $payoutService->initiateOrganizerPayout(
            $organizer,
            50000.00,
            'paystack',
            ['account_number' => '1234567890', 'bank_code' => '058'],
            'Monthly payout',
            'payout-key-123',
        );
    }

    public function test_payout_blocked_when_organizer_has_active_fraud_events(): void
    {
        $user = $this->makeUser();
        $organizer = $this->makeOrganizer($user);

        FraudEvent::factory()->create([
            'user_id' => $user->id,
            'status' => 'auto_blocked',
            'risk_score' => 85,
            'risk_level' => 'high',
        ]);

        $paymentGatewayService = Mockery::mock(PaymentGatewayService::class);
        $payoutService = new PayoutService($paymentGatewayService);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('active fraud events');

        $payoutService->initiateOrganizerPayout(
            $organizer,
            50000.00,
            'paystack',
            ['account_number' => '1234567890', 'bank_code' => '058'],
            'Monthly payout',
            'payout-key-456',
        );
    }

    public function test_payout_idempotent_with_same_key(): void
    {
        $user = $this->makeUser();
        $organizer = $this->makeOrganizer($user);

        $paymentGatewayService = Mockery::mock(PaymentGatewayService::class);
        $paymentGatewayService->shouldReceive('initiatePayout')->once()->andReturn([
            'id' => 'trx_123',
            'reference' => 'ref_123',
            'status' => 'pending',
        ]);

        $payoutService = new PayoutService($paymentGatewayService);

        $payout1 = $payoutService->initiateOrganizerPayout(
            $organizer,
            50000.00,
            'paystack',
            ['account_number' => '1234567890', 'bank_code' => '058'],
            'Monthly payout',
            'idempotent-key-789',
        );

        $payout2 = $payoutService->initiateOrganizerPayout(
            $organizer,
            50000.00,
            'paystack',
            ['account_number' => '1234567890', 'bank_code' => '058'],
            'Monthly payout',
            'idempotent-key-789',
        );

        $this->assertEquals($payout1->id, $payout2->id);
        $this->assertDatabaseCount('organizer_payouts', 1);
    }

    public function test_payout_succeeds_for_clean_organizer(): void
    {
        $user = $this->makeUser();
        $organizer = $this->makeOrganizer($user);

        $paymentGatewayService = Mockery::mock(PaymentGatewayService::class);
        $paymentGatewayService->shouldReceive('initiatePayout')->once()->andReturn([
            'id' => 'trx_success_123',
            'reference' => 'ref_success_123',
            'status' => 'pending',
        ]);

        $payoutService = new PayoutService($paymentGatewayService);

        $payout = $payoutService->initiateOrganizerPayout(
            $organizer,
            50000.00,
            'paystack',
            ['account_number' => '1234567890', 'bank_code' => '058'],
            'Monthly payout',
            'clean-payout-key',
        );

        $this->assertEquals('processing', $payout->status);
        $this->assertEquals('trx_success_123', $payout->gateway_transfer_id);
        $this->assertDatabaseHas('organizer_payouts', [
            'id' => $payout->id,
            'amount' => 50000.00,
            'gateway' => 'paystack',
            'payout_idempotency_key' => 'clean-payout-key',
        ]);
    }
}
