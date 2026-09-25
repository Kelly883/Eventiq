<?php

namespace Tests\Feature;

use App\Features\Refunds\Models\RefundPolicy;
use App\Features\Refunds\Models\RefundRequest;
use App\Features\Payment\Models\PaymentMethod;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Role;
use App\Features\Checkout\Models\Ticket;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RefundRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('payment.gateways.paystack.secret_key', 'test-secret-key');
        config()->set('payment.gateways.paystack.public_key', 'test-public-key');
        config()->set('payment.gateways.paystack.payment_url', 'https://api.paystack.co');

        Http::fake([
            'https://api.paystack.co/refund' => Http::response([
                'status' => true,
                'data' => [
                    'id' => 'rf_' . \Illuminate\Support\Str::random(8),
                    'status' => 'processed',
                ],
            ], 200),
        ]);
    }

    private function makeUser(string $role = 'attendee'): User
    {
        $user = User::factory()->create(['emailVerified' => true]);
        $user->organizer()->firstOrCreate([], ['displayName' => $user->name ?? 'Test User']);

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

    private function createTicket(User $user, ?Event $event = null, string $status = 'valid', ?float $ticketPrice = null): Ticket
    {
        $event = $event ?? Event::factory()->create(['start_datetime' => now()->addDays(7)]);
        $tier = TicketTier::factory()->create([
            'event_id' => $event->id,
            'price' => $ticketPrice ?? 100.00,
        ]);

        $order = \App\Features\Checkout\Models\Order::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'status' => 'completed',
            'payment_gateway' => 'paystack',
            'gateway_transaction_id' => 'tx_' . \Illuminate\Support\Str::random(16),
        ]);

        return Ticket::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'ticket_tier_id' => $tier->id,
            'order_id' => $order->id,
            'status' => $status,
        ]);
    }

    // ------------------------------------------------------------------
    // Auth / Authorization
    // ------------------------------------------------------------------

    public function test_refund_request_requires_authentication(): void
    {
        $this->postJson('/api/refunds/request', [])->assertUnauthorized();
    }

    // ------------------------------------------------------------------
    // Validation
    // ------------------------------------------------------------------

    public function test_refund_request_requires_ticket_id(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(422);
    }

    public function test_refund_request_requires_reason(): void
    {
        $user = $this->makeUser();
        $ticket = $this->createTicket($user);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(422);
    }

    public function test_refund_request_requires_valid_refund_method(): void
    {
        $user = $this->makeUser();
        $ticket = $this->createTicket($user);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'Event cancelled',
            'refund_method' => 'bitcoin',
        ]);

        $response->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // Ownership check
    // ------------------------------------------------------------------

    public function test_cannot_request_refund_for_other_users_ticket(): void
    {
        $user = $this->makeUser();
        $otherUser = $this->makeUser();
        $ticket = $this->createTicket($otherUser);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // Refund window check
    // ------------------------------------------------------------------

    public function test_cannot_request_refund_after_event_starts(): void
    {
        $user = $this->makeUser();
        $event = Event::factory()->create(['start_datetime' => now()->subDays(1)]);
        $ticket = $this->createTicket($user, $event);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // Duplicate refund request
    // ------------------------------------------------------------------

    public function test_duplicate_refund_request_returns_409(): void
    {
        $user = $this->makeUser();
        $ticket = $this->createTicket($user);

        $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ])->assertStatus(201);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(409);
    }

    // ------------------------------------------------------------------
    // Successful refund request
    // ------------------------------------------------------------------

    public function test_successful_refund_request(): void
    {
        $user = $this->makeUser();
        $ticket = $this->createTicket($user);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
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
                    'refundMethod',
                    'expectedProcessingDays',
                    'referenceNumber',
                ],
            ]);

        $this->assertDatabaseHas('refund_requests', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);
    }

    public function test_refund_amount_calculated_correctly(): void
    {
        $user = $this->makeUser();
        $event = Event::factory()->create(['start_datetime' => now()->addDays(7)]);

        // Create policy with 80% refund before event
        RefundPolicy::factory()->create([
            'event_id' => $event->id,
            'refund_percentage_before_event' => 80.00,
            'is_active' => true,
        ]);

        $ticket = $this->createTicket($user, $event, 'valid', 250.00);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(201);
        $this->assertEquals(200.00, $response->json('data.refundAmount'));
    }

    public function test_refund_status_auto_approved_when_no_approval_required(): void
    {
        $user = $this->makeUser();
        $event = Event::factory()->create(['start_datetime' => now()->addDays(7)]);
        $tier = TicketTier::factory()->create(['event_id' => $event->id, 'price' => 100.00]);
        $ticket = $this->createTicket($user, $event);

        // Policy that doesn't require approval
        RefundPolicy::factory()->create([
            'event_id' => $event->id,
            'requires_approval' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(201);
        $this->assertEquals('approved', $response->json('data.status'));
    }

    public function test_refund_status_pending_when_approval_required(): void
    {
        $user = $this->makeUser();
        $event = Event::factory()->create(['start_datetime' => now()->addDays(7)]);
        $tier = TicketTier::factory()->create(['event_id' => $event->id, 'price' => 100.00]);
        $ticket = $this->createTicket($user, $event);

        // Policy that requires approval
        RefundPolicy::factory()->create([
            'event_id' => $event->id,
            'requires_approval' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(201);
        $data = $response->json();
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('pending', $data['data']['status']);
    }

    // ------------------------------------------------------------------
    // Audit log
    // ------------------------------------------------------------------

    public function test_refund_request_creates_audit_log(): void
    {
        $user = $this->makeUser();
        $ticket = $this->createTicket($user);

        $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ])->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'refund.requested',
        ]);
    }

    // ------------------------------------------------------------------
    // Rate limiting
    // ------------------------------------------------------------------

    public function test_refund_request_rate_limited(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $ticket = $this->createTicket($user);
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/refunds/request', [
                    'ticket_id' => $ticket->id,
                    'reason' => 'event_cancelled',
                    'refund_method' => 'original_payment_method',
                ])
                ->assertStatus(201);
        }

        $ticket = $this->createTicket($user);
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/refunds/request', [
                'ticket_id' => $ticket->id,
                'reason' => 'event_cancelled',
                'refund_method' => 'original_payment_method',
            ])
            ->assertStatus(429);
    }

    // ------------------------------------------------------------------
    // Edge cases
    // ------------------------------------------------------------------

    public function test_cannot_refund_void_ticket(): void
    {
        $user = $this->makeUser();
        $ticket = $this->createTicket($user, null, 'void');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_refund_checked_in_ticket(): void
    {
        $user = $this->makeUser();
        $ticket = $this->createTicket($user, null, 'checked_in');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
        ]);

        $response->assertStatus(403);
    }
}
