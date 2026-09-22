<?php

namespace Tests\Feature;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Ticket;
use App\Features\Delivery\Models\DeliveryEvent;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class DeliveryEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::for('delivery-status', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('delivery-resend', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
    }

    protected function tearDown(): void
    {
        RateLimiter::for('delivery-status', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('127.0.0.1'));
        RateLimiter::for('delivery-resend', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('127.0.0.1'));
        parent::tearDown();
    }

    private function makeUser(string $role = 'attendee'): User
    {
        $user = User::factory()->create(['emailVerified' => true]);
        $user->organizer()->firstOrCreate([], ['displayName' => $user->name ?? 'Test User']);

        if ($role === 'admin') {
            $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator', 'isSystemRole' => true]);
            if (!$user->roles()->where('name', 'admin')->exists()) {
                $user->roles()->attach($adminRole);
            }
        }

        return $user;
    }

    private function seedTicket(User $user, string $ticketStatus = 'valid'): Ticket
    {
        $organizer = $user->organizer ?? $user->organizer()->create(['displayName' => $user->name ?? 'Test User']);

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'status' => 'published',
            'is_public' => true,
        ]);

        $tier = TicketTier::factory()->create([
            'event_id' => $event->id,
            'status' => 'published',
            'quantity' => 100,
            'sold_count' => 0,
            'price' => 15000.00,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'total_amount' => 15000.00,
            'currency' => 'NGN',
            'status' => 'completed',
            'payment_gateway' => 'paystack',
            'payment_intent_id' => 'pi_' . (string) \Illuminate\Support\Str::uuid(),
        ]);

        return Ticket::factory()->create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'ticket_tier_id' => $tier->id,
            'order_id' => $order->id,
            'status' => $ticketStatus,
        ]);
    }

    // ------------------------------------------------------------------
    // GET /api/tickets/:ticketId/delivery-status
    // ------------------------------------------------------------------

    public function test_delivery_status_requires_authentication(): void
    {
        $response = $this->getJson('/api/tickets/some-id/delivery-status');
        $response->assertUnauthorized();
    }

    public function test_delivery_status_returns_correct_status(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        DeliveryEvent::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'event_id' => $ticket->event_id,
            'channel' => 'email',
            'status' => 'delivered',
            'recipient' => 'test@example.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tickets/' . $ticket->id . '/delivery-status');

        $response->assertOk()
            ->assertJsonPath('data.ticket_id', $ticket->id)
            ->assertJsonPath('data.status', 'valid')
            ->assertJsonStructure([
                'data' => ['ticket_id', 'status', 'delivery_history' => [['id', 'channel', 'status', 'attempt_count']]],
            ]);

        $history = $response->json('data.delivery_history');
        $this->assertCount(1, $history);
        $this->assertEquals('email', $history[0]['channel']);
        $this->assertEquals('delivered', $history[0]['status']);
    }

    public function test_delivery_status_requires_ownership(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $ticket = $this->seedTicket($owner);

        $response = $this->actingAs($other, 'sanctum')
            ->getJson('/api/tickets/' . $ticket->id . '/delivery-status');

        $response->assertForbidden();
    }

    public function test_delivery_status_returns_404_for_nonexistent_ticket(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tickets/nonexistent-id/delivery-status');

        $response->assertNotFound();
    }

    // ------------------------------------------------------------------
    // POST /api/tickets/:ticketId/resend-delivery
    // ------------------------------------------------------------------

    public function test_resend_delivery_requires_authentication(): void
    {
        $response = $this->postJson('/api/tickets/some-id/resend-delivery', [
            'channel' => 'email',
            'recipient' => 'test@example.com',
        ]);
        $response->assertUnauthorized();
    }

    public function test_resend_delivery_success(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/resend-delivery', [
                'channel' => 'email',
                'recipient' => 'newemail@example.com',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonStructure(['data' => ['message', 'delivery_event_id', 'status', 'next_retry_at']]);

        $event = DeliveryEvent::where('ticket_id', $ticket->id)->where('channel', 'email')->first();
        $this->assertNotNull($event);
        $this->assertEquals('pending', $event->status);
        $this->assertEquals('newemail@example.com', $event->recipient);
    }

    public function test_resend_delivery_updates_status_to_pending(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        $existingEvent = DeliveryEvent::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'channel' => 'email',
            'status' => 'failed',
            'attempt_count' => 1,
            'max_attempts' => 3,
            'recipient' => 'old@example.com',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/resend-delivery', [
                'channel' => 'email',
                'recipient' => 'updated@example.com',
            ]);

        $response->assertOk()->assertJsonPath('data.status', 'pending');

        $existingEvent->refresh();
        $this->assertEquals('pending', $existingEvent->status);
        $this->assertEquals('updated@example.com', $existingEvent->recipient);
        $this->assertNotNull($existingEvent->next_retry_at);
        $this->assertEquals(2, $existingEvent->attempt_count);
    }

    public function test_resend_delivery_rejects_invalid_email(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/resend-delivery', [
                'channel' => 'email',
                'recipient' => 'not-an-email',
            ]);

        $response->assertStatus(400);
    }

    public function test_resend_delivery_rejects_max_attempts_exceeded(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        DeliveryEvent::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'channel' => 'email',
            'status' => 'failed',
            'attempt_count' => 3,
            'max_attempts' => 3,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/resend-delivery', [
                'channel' => 'email',
                'recipient' => 'test@example.com',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('reason', 'max_attempts_exceeded');
    }

    public function test_resend_delivery_rejects_fraud_blocked_ticket(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        // Use 'void' which is a valid blocked status in the DB
        $ticket->status = 'void';
        $ticket->save();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/resend-delivery', [
                'channel' => 'email',
                'recipient' => 'test@example.com',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('reason', 'ticket_blocked');
    }

    public function test_delivery_status_pagination(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        // Create 25 delivery events to test pagination
        for ($i = 0; $i < 25; $i++) {
            DeliveryEvent::factory()->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'event_id' => $ticket->event_id,
                'channel' => 'email',
                'status' => 'delivered',
                'recipient' => 'test@example.com',
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tickets/' . $ticket->id . '/delivery-status?page=2');

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.pagination.current_page'));
        $this->assertEquals(25, $response->json('data.pagination.total'));
    }

    public function test_resend_delivery_rejects_refunded_order(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        // Update order status to refunded
        $order = $ticket->order;
        $order->update(['status' => 'refunded']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/resend-delivery', [
                'channel' => 'email',
                'recipient' => 'test@example.com',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('reason', 'order_refunded');
    }

    public function test_resend_delivery_rejects_invalid_channel(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/resend-delivery', [
                'channel' => 'invalid',
                'recipient' => 'test@example.com',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['channel']);
    }

    public function test_resend_delivery_requires_ownership(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $ticket = $this->seedTicket($owner);

        $response = $this->actingAs($other, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/resend-delivery', [
                'channel' => 'email',
                'recipient' => 'test@example.com',
            ]);

        $response->assertForbidden();
    }

    public function test_resend_delivery_returns_404_for_nonexistent_ticket(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/nonexistent-id/resend-delivery', [
                'channel' => 'email',
                'recipient' => 'test@example.com',
            ]);

        $response->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Rate limiting
    // ------------------------------------------------------------------

    public function test_delivery_status_is_rate_limited(): void
    {
        RateLimiter::for('delivery-status', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('127.0.0.1'));

        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/tickets/' . $ticket->id . '/delivery-status')
                ->assertOk();
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tickets/' . $ticket->id . '/delivery-status');

        $response->assertStatus(429);
    }

    public function test_delivery_resend_is_rate_limited(): void
    {
        RateLimiter::for('delivery-resend', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('127.0.0.1'));

        $user = $this->makeUser();

        for ($i = 0; $i < 10; $i++) {
            $ticket = $this->seedTicket($user);
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/tickets/' . $ticket->id . '/resend-delivery', [
                    'channel' => 'email',
                    'recipient' => 'test' . $i . '@example.com',
                ])
                ->assertOk();
        }

        $ticket = $this->seedTicket($user);
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/tickets/' . $ticket->id . '/resend-delivery', [
                'channel' => 'email',
                'recipient' => 'over@example.com',
            ]);

        $response->assertStatus(429);
    }
}
