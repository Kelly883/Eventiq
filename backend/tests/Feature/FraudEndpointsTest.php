<?php

namespace Tests\Feature;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Payment;
use App\Features\Fraud\Models\FraudEvent;
use App\Features\Inventory\Models\TicketInventory;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class FraudEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::for('discovery', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('webhooks', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
    }

    protected function tearDown(): void
    {
        RateLimiter::for('discovery', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by('127.0.0.1'));
        RateLimiter::for('webhooks', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by('127.0.0.1'));
        parent::tearDown();
    }

    private function makeUser(string $role = 'attendee', int $accountAgeDays = 30): User
    {
        $user = User::factory()->create([
            'emailVerified' => true,
            'created_at' => now()->subDays($accountAgeDays),
        ]);

        if ($role === 'admin') {
            $adminRole = Role::create(['name' => 'admin', 'description' => 'Administrator', 'isSystemRole' => true]);
            $user->roles()->attach($adminRole);
        }

        return $user;
    }

    private function seedTicketTier(User $organizer): TicketTier
    {
        $event = Event::factory()->create([
            'organizer_id' => $organizer->organizer()->create(['displayName' => $organizer->name])->id,
            'status' => 'published',
        ]);
        return TicketTier::factory()->create([
            'event_id' => $event->id,
            'status' => 'published',
            'quantity' => 100,
            'sold_count' => 0,
            'price' => 15000.00,
        ]);
    }

    // ------------------------------------------------------------------
    // POST /api/checkout/create-payment-intent
    // ------------------------------------------------------------------

    public function test_create_payment_intent_requires_authentication(): void
    {
        $response = $this->postJson('/api/checkout/create-payment-intent', [
            'event_id' => 1,
            'gateway' => 'paystack',
            'items' => [['ticket_tier_id' => 1, 'quantity' => 1]],
        ]);
        $response->assertUnauthorized();
    }

    public function test_create_payment_intent_returns_order_for_valid_cart(): void
    {
        $user = $this->makeUser();
        $tier = $this->seedTicketTier($user);

        // Mock the Paystack gateway
        $paystack = \Mockery::mock(\App\Features\Payment\Services\PaystackService::class);
        $paystack->shouldReceive('initializeTransaction')->once()->andReturn([
            'authorization_url' => 'https://paystack.com/pay/test',
            'access_code' => 'test-access-code',
            'reference' => 'test-reference',
        ]);
        $this->app->instance(\App\Features\Payment\Services\PaystackService::class, $paystack);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/checkout/create-payment-intent', [
                'event_id' => $tier->event_id,
                'gateway' => 'paystack',
                'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 1]],
            ]);

        $response->assertOk()
            ->assertJsonStructure(['order_id', 'reference', 'gateway', 'gateway_data']);
    }

    public function test_create_payment_intent_rejects_high_risk_transaction(): void
    {
        // New user (account < 24h) + multiple orders + high value = score > 70
        $user = $this->makeUser('attendee', 0); // Created now = 0 days old
        $tier = $this->seedTicketTier($user);

        // Create multiple recent orders to trigger velocity fraud flag
        Order::factory()->count(4)->create([
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/checkout/create-payment-intent', [
                'event_id' => $tier->event_id,
                'gateway' => 'paystack',
                'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 1]],
            ]);

        $response->assertStatus(403)
            ->assertJsonStructure(['message', 'reason']);
    }

    public function test_create_payment_intent_returns_verification_flag_for_medium_risk(): void
    {
        // New user with medium-value order = medium risk
        $user = $this->makeUser('attendee', 0); // Created now = 0 days old
        $event = Event::factory()->create([
            'organizer_id' => $user->organizer()->create(['displayName' => $user->name])->id,
            'status' => 'published',
        ]);
        TicketTier::factory()->create([
            'event_id' => $event->id,
            'status' => 'published',
            'quantity' => 100,
            'sold_count' => 0,
            'price' => 1000.00, // Lower value
        ]);
        $tier = TicketTier::where('event_id', $event->id)->first();

        // Mock the Paystack gateway
        $paystack = Mockery::mock(\App\Features\Payment\Services\PaystackService::class);
        $paystack->shouldReceive('initializeTransaction')->once()->andReturn([
            'authorization_url' => 'https://paystack.com/pay/test',
            'access_code' => 'test-access-code',
            'reference' => 'test-reference',
        ]);
        $this->app->instance(\App\Features\Payment\Services\PaystackService::class, $paystack);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/checkout/create-payment-intent', [
                'event_id' => $tier->event_id,
                'gateway' => 'paystack',
                'items' => [['ticket_tier_id' => $tier->id, 'quantity' => 1]],
            ]);

        $response->assertOk()
            ->assertJsonStructure(['order_id', 'reference', 'gateway', 'gateway_data', 'requires_additional_verification']);
        $this->assertTrue($response->json('requires_additional_verification'));
    }

    // ------------------------------------------------------------------
    // POST /api/webhooks/payment-provider
    // ------------------------------------------------------------------

    public function test_webhook_verifies_signature_before_processing(): void
    {
        $response = $this->postJson('/api/webhooks/payment-provider', [
            'event' => 'charge.success',
            'data' => ['reference' => 'test-ref'],
        ], ['x-paystack-signature' => 'invalid']);

        $response->assertStatus(401);
    }

    public function test_webhook_acknowledges_unknown_reference(): void
    {
        // Mock Paystack service to return valid signature
        $paystack = Mockery::mock(\App\Features\Payment\Services\PaystackService::class);
        $paystack->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        $paystack->shouldReceive('verifyTransaction')->never();
        $this->app->instance(\App\Features\Payment\Services\PaystackService::class, $paystack);

        $response = $this->postJson('/api/webhooks/payment-provider', [
            'event' => 'charge.success',
            'data' => ['reference' => 'unknown-ref-' . rand(10000, 99999)],
        ], ['x-paystack-signature' => 'valid']);

        $response->assertOk()->assertJson(['received' => true]);
    }

    // ------------------------------------------------------------------
    // POST /api/fraud/detect-duplicate-tickets
    // ------------------------------------------------------------------

    public function test_duplicate_ticket_detection_requires_auth(): void
    {
        $response = $this->postJson('/api/fraud/duplicate-tickets', [
            'ticket_tier_id' => 1,
            'qr_code_data' => 'QR123',
        ]);
        $response->assertUnauthorized();
    }

    public function test_duplicate_ticket_detection_returns_false_for_unique_qr(): void
    {
        $admin = $this->makeUser('admin');
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/fraud/duplicate-tickets', [
                'ticket_tier_id' => 1,
                'qr_code_data' => 'UNIQUE_QR_' . rand(10000, 99999),
            ]);

        $response->assertOk();
        $this->assertFalse($response->json('duplicate'));
    }

    public function test_duplicate_ticket_detection_returns_true_for_matching_qr(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();
        $tier = $this->seedTicketTier($user);
        $order = Order::factory()->create(['user_id' => $user->id]);
        $qrCode = 'DUPLICATE_QR_' . rand(10000, 99999);

        // Create two VALID tickets with the same QR code for the same tier -
        // void/purged tickets are intentionally excluded from the duplicate
        // check (they cannot be used for entry), so pin the status here.
        \App\Features\Checkout\Models\Ticket::factory()->create([
            'order_id' => $order->id,
            'ticket_tier_id' => $tier->id,
            'qr_code_data' => $qrCode,
            'status' => 'valid',
        ]);
        \App\Features\Checkout\Models\Ticket::factory()->create([
            'order_id' => Order::factory()->create(['user_id' => $user->id])->id,
            'ticket_tier_id' => $tier->id,
            'qr_code_data' => $qrCode,
            'status' => 'valid',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/fraud/duplicate-tickets', [
                'ticket_tier_id' => $tier->id,
                'qr_code_data' => $qrCode,
            ]);

        $response->assertOk();
        $this->assertTrue($response->json('duplicate'));
    }

    // ------------------------------------------------------------------
    // POST /api/fraud/velocity-check
    // ------------------------------------------------------------------

    public function test_velocity_check_requires_auth(): void
    {
        $response = $this->postJson('/api/fraud/velocity', [
            'user_id' => 1,
            'amount' => 100,
        ]);
        $response->assertUnauthorized();
    }

    public function test_velocity_check_returns_suspicious_for_high_velocity(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();

        // Create multiple orders in the last hour to trigger velocity limit
        Order::factory()->count(5)->create([
            'user_id' => $user->id,
            'created_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/fraud/velocity', [
                'user_id' => $user->id,
                'amount' => 1000,
            ]);

        $response->assertOk();
        $this->assertTrue($response->json('exceeded'));
        $this->assertGreaterThanOrEqual(5, $response->json('count_1h'));
    }

    // ------------------------------------------------------------------
    // GET /api/admin/fraud/flagged-transactions
    // ------------------------------------------------------------------

    public function test_flagged_transactions_requires_admin_role(): void
    {
        $user = $this->makeUser();
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/fraud/flagged-transactions');
        $response->assertForbidden();
    }

    public function test_flagged_transactions_returns_paginated_list(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();

        // Create some flagged fraud events
        FraudEvent::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'flagged',
            'risk_level' => 'high',
        ]);
        FraudEvent::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/fraud/flagged-transactions');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['data' => ['*' => ['id', 'user_id', 'risk_score', 'status', 'fraud_type']]],
            ]);
    }

    public function test_flagged_transactions_filters_by_risk_level(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();

        FraudEvent::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'flagged',
            'risk_level' => 'high',
        ]);
        FraudEvent::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'flagged',
            'risk_level' => 'medium',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/fraud/flagged-transactions?risk_level=high');

        $response->assertOk();
        $data = collect($response->json('data.data'));
        $this->assertGreaterThan(0, $data->count());
        $data->each(function ($item) {
            $this->assertEquals('high', $item['risk_level']);
        });
    }

    // ------------------------------------------------------------------
    // GET /api/admin/fraud/transaction-details/:fraudEventId
    // ------------------------------------------------------------------

    public function test_transaction_details_requires_admin(): void
    {
        $user = $this->makeUser();
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/fraud/transaction-details/some-id');
        $response->assertForbidden();
    }

    public function test_transaction_details_returns_full_analysis(): void
    {
        $admin = $this->makeUser('admin');
        $user = $this->makeUser();

        $fraudEvent = FraudEvent::factory()->create([
            'user_id' => $user->id,
            'status' => 'flagged',
            'risk_score' => 85.50,
            'risk_level' => 'high',
            'fraud_factors' => ['new_user', 'high_value'],
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/fraud/transaction-details/' . $fraudEvent->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $fraudEvent->id)
            ->assertJsonPath('data.risk_score', '85.50')
            ->assertJsonStructure([
                'data' => ['id', 'user_id', 'risk_score', 'risk_level', 'fraud_factors', 'status', 'fraud_type'],
            ]);
    }

    // ------------------------------------------------------------------
    // Rate limiting
    // ------------------------------------------------------------------

    public function test_fraud_endpoints_are_rate_limited(): void
    {
        RateLimiter::for('discovery', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by('127.0.0.1'));

        $admin = $this->makeUser('admin');

        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($admin, 'sanctum')
                ->postJson('/api/fraud/duplicate-tickets', [
                    'ticket_tier_id' => 1,
                    'qr_code_data' => 'QR_' . $i,
                ])->assertOk();
        }

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/fraud/duplicate-tickets', [
                'ticket_tier_id' => 1,
                'qr_code_data' => 'QR_OVER_LIMIT',
            ]);

        $response->assertStatus(429);
    }
}
