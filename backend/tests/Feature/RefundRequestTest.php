<?php

namespace Tests\Feature;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Ticket;
use App\Features\Payment\Models\PaymentMethod;
use App\Models\Role;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RefundRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::for('refund-request', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
    }

    protected function tearDown(): void
    {
        RateLimiter::for('refund-request', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('127.0.0.1'));
        parent::tearDown();
    }

    private function makeUser(string $role = 'attendee'): User
    {
        $user = User::factory()->create(['emailVerified' => true]);

        // original_payment_method refunds require a payment method on file.
        PaymentMethod::create([
            'user_id' => $user->id,
            'gateway' => 'paystack',
            'gateway_payment_method_id' => 'pm_' . strtolower($user->id),
            'type' => 'card',
            'is_default' => true,
            'last_four' => '4242',
            'brand' => 'visa',
        ]);

        if ($role === 'admin') {
            $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator', 'isSystemRole' => true]);
            if (!$user->roles()->where('name', 'admin')->exists()) {
                $user->roles()->attach($adminRole);
            }
        }
        return $user;
    }

    private function makeTicket(User $user, ?string $eventStartDate = null): Ticket
    {
        $organizer = $user->organizer()->firstOrCreate([], ['displayName' => $user->name]);

        $event = \App\Models\Event::factory()->create([
            'organizer_id' => $organizer->id,
            'status' => 'published',
            'is_public' => true,
            'start_datetime' => $eventStartDate ?? now()->addDays(7)->toDateTimeString(),
            'end_datetime' => now()->addDays(8)->toDateTimeString(),
        ]);

        $tier = TicketTier::factory()->create([
            'event_id' => $event->id,
            'status' => 'published',
            'quantity' => 100,
            'sold_count' => 0,
            'price' => 5000.00,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'total_amount' => 5000.00,
            'currency' => 'NGN',
            'status' => 'completed',
            'payment_gateway' => 'paystack',
            'payment_intent_id' => 'pi_' . \Illuminate\Support\Str::uuid(),
            // original_payment_method refunds require a gateway transaction.
            'gateway_transaction_id' => 'tx_' . \Illuminate\Support\Str::uuid(),
        ]);

        return Ticket::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'ticket_tier_id' => $tier->id,
            'order_id' => $order->id,
            'status' => 'valid',
            'attendee_name' => $user->name,
            'attendee_email' => $user->email,
        ]);
    }

    // ------------------------------------------------------------------
    // Auth / Authorization
    // ------------------------------------------------------------------

    public function test_request_refund_requires_authentication(): void
    {
        $this->postJson('/api/refunds/request', [
            'ticket_id' => 'some-id',
            'reason' => 'test',
            'refund_method' => 'original_payment_method',
        ])->assertUnauthorized();
    }

    public function test_request_refund_requires_admin_or_owner(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $ticket = $this->makeTicket($other);

        // Neither the admin role nor ticket ownership -> forbidden.
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/refunds/request', [
                'ticket_id' => $ticket->id,
                'reason' => 'event_cancelled',
                'refund_method' => 'original_payment_method',
            ])
            ->assertForbidden();
    }

    // ------------------------------------------------------------------
    // POST /api/refunds/request
    // ------------------------------------------------------------------

    public function test_request_refund_valid(): void
    {
        $admin = $this->makeUser('admin');
        $ticket = $this->makeTicket($admin);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.originalAmount', '5000.00')
            ->assertJsonPath('data.refundAmount', '5000.00')
            ->assertJsonPath('data.refundPercentage', '100.00')
            ->assertJsonPath('data.expectedProcessingDays', 3);

        $this->assertDatabaseHas('refund_requests', [
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
            'status' => 'pending',
        ]);
    }

    public function test_request_refund_invalid_reason(): void
    {
        $admin = $this->makeUser('admin');
        $ticket = $this->makeTicket($admin);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'invalid_reason',
            'refund_method' => 'original_payment_method',
        ]);

        // FormRequest validation failures return 422, not 400.
        $response->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_request_refund_invalid_method(): void
    {
        $admin = $this->makeUser('admin');
        $ticket = $this->makeTicket($admin);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'invalid_method',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('refund_method');
    }

    public function test_request_refund_ticket_not_owned(): void
    {
        $admin = $this->makeUser('admin');
        $other = $this->makeUser();
        $ticket = $this->makeTicket($other);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(403);
    }

    public function test_request_refund_outside_refund_window(): void
    {
        $admin = $this->makeUser('admin');
        $ticket = $this->makeTicket($admin);

        // Event already started - should be outside refund window
        $ticket->event->update(['start_datetime' => now()->subDay()]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(403);
    }

    public function test_request_refund_duplicate(): void
    {
        $admin = $this->makeUser('admin');
        $ticket = $this->makeTicket($admin);

        // First refund request
        $this->actingAs($admin, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ])->assertStatus(201);

        // Second refund request for same ticket
        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(409);
    }

    public function test_request_refund_with_idempotency_key(): void
    {
        $admin = $this->makeUser('admin');
        $ticket = $this->makeTicket($admin);

        $payload = [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ];

        $response1 = $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Idempotency-Key', 'unique-key-123')
            ->postJson('/api/refunds/request', $payload);
        $response1->assertStatus(201);

        $response2 = $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Idempotency-Key', 'unique-key-123')
            ->postJson('/api/refunds/request', $payload);

        // Second request should return the same refund request
        $response2->assertStatus(201)
            ->assertJsonPath('data.refundRequestId', $response1->json('data.refundRequestId'));

        $this->assertDatabaseCount('refund_requests', 1);
    }

    public function test_request_refund_response_structure(): void
    {
        $admin = $this->makeUser('admin');
        $ticket = $this->makeTicket($admin);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'refundRequestId',
                    'status',
                    'originalAmount',
                    'refundAmount',
                    'refundPercentage',
                    'refundMethod',
                    'reason',
                    'expectedProcessingDays',
                    'referenceNumber',
                ],
            ]);
    }

    // ------------------------------------------------------------------
    // Rate limiting
    // ------------------------------------------------------------------

    public function test_request_refund_rate_limited(): void
    {
        RateLimiter::for('refund-request', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(3)->by('127.0.0.1'));

        $admin = $this->makeUser('admin');

        // Each request needs its own ticket so the duplicate guard does not
        // interfere with the throttling assertions.
        for ($i = 0; $i < 3; $i++) {
            $ticket = $this->makeTicket($admin);

            $this->actingAs($admin, 'sanctum')
                ->postJson('/api/refunds/request', [
                    'ticket_id' => $ticket->id,
                    'reason' => 'event_cancelled',
                    'refund_method' => 'original_payment_method',
                ])
                ->assertCreated();
        }

        $ticket = $this->makeTicket($admin);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/refunds/request', [
                'ticket_id' => $ticket->id,
                'reason' => 'event_cancelled',
                'refund_method' => 'original_payment_method',
            ])
            ->assertStatus(429);
    }
}
