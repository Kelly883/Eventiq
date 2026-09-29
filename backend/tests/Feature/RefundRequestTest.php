<?php

namespace Tests\Feature;

use App\Features\Refunds\Enums\RefundMethodEnum;
use App\Features\Refunds\Enums\RefundReasonEnum;
use App\Features\Refunds\Models\RefundPolicy;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Ticket;
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
        if ($role === 'admin') {
            $adminRole = \App\Models\Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator', 'isSystemRole' => true]);
            if (!$user->roles()->where('name', 'admin')->exists()) {
                $user->roles()->attach($adminRole);
            }
        }
        return $user;
    }

    private function makeTicket(User $user, ?string $eventStartDate = null): Ticket
    {
        $organizer = $user->organizer ?? $user->organizer()->create(['displayName' => $user->name]);

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
        ]);

        return \App\Models\Ticket::factory()->create([
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
        $ticket = $this->makeTicket($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/refunds/request', [
                'ticket_id' => $ticket->id,
                'reason' => 'test',
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
            ->assertJsonPath('data.refund_amount', (float) ($ticket->ticketTier->price * 100 / 100))
            ->assertJsonPath('data.refund_percentage', 100.00)
            ->assertJsonPath('data.reference_number')
            ->assertJsonPath('data.expected_processing_days')
            ->assertJsonPath('data.refund_request_id');
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

        $response->assertStatus(400);
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

        $response->assertStatus(400);
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
        ]);

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

        $response1 = $this->actingAs($admin, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
            'idempotency_key' => 'unique-key-123',
        ]);

        $response2 = $this->actingAs($admin, 'sanctum')->postJson('/api/refunds/request', [
            'ticket_id' => $ticket->id,
            'reason' => 'event_cancelled',
            'refund_method' => 'original_payment_method',
            'idempotency_key' => 'unique-key-123',
        ]);

        // Second request should return the same refund request
        $response2->assertStatus(201)
            ->assertJsonPath('data.refund_request_id', $response1->json('data.refund_request_id'));
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
            ->assertJsonPath('data.refund_request_id')
            ->assertJsonPath('data.status')
            ->assertJsonPath('data.refund_amount')
            ->assertJsonPath('data.expected_processing_days')
            ->assertJsonPath('data.reference_number')
            ->assertJsonPath('data.refund_percentage');
    }

    // ------------------------------------------------------------------
    // Rate limiting
    // ------------------------------------------------------------------

    public function test_request_refund_rate_limited(): void
    {
        RateLimiter::for('refund-request', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(3)->by('127.0.0.1'));

        $admin = $this->makeUser('admin');
        $ticket = $this->makeTicket($admin);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($admin, 'sanctum')
                ->postJson('/api/refunds/request', [
                    'ticket_id' => $ticket->id,
                    'reason' => 'event_cancelled',
                    'refund_method' => 'original_payment_method',
                ])
                ->assertOk();
        }

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/refunds/request', [
                'ticket_id' => $ticket->id,
                'reason' => 'event_cancelled',
                'refund_method' => 'original_payment_method',
            ])
            ->assertStatus(429);
    }
}
