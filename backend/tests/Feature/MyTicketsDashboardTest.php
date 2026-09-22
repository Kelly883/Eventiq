<?php

namespace Tests\Feature;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Ticket;
use App\Features\Delivery\Models\DeliveryEvent;
use App\Features\Dashboard\Models\UserDashboardPreference;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class MyTicketsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::for('dashboard-metrics', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('dashboard-preferences', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
    }

    protected function tearDown(): void
    {
        RateLimiter::for('dashboard-metrics', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('127.0.0.1'));
        RateLimiter::for('dashboard-preferences', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('127.0.0.1'));
        parent::tearDown();
    }

    private function makeUser(): User
    {
        return User::factory()->create(['emailVerified' => true]);
    }

    private function seedTicket(User $user, string $when = 'future'): Ticket
    {
        $organizer = $user->organizer ?? $user->organizer()->create(['displayName' => $user->name ?? 'Test User']);

        $start = $when === 'future' ? now()->addDays(10) : now()->subDays(10);
        $end = $when === 'future' ? now()->addDays(10)->addHours(2) : now()->subDays(9);

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'status' => 'published',
            'is_public' => true,
            'start_datetime' => $start,
            'end_datetime' => $end,
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
            'status' => 'valid',
        ]);
    }

    // ------------------------------------------------------------------
    // GET /api/my-tickets
    // ------------------------------------------------------------------

    public function test_my_tickets_requires_authentication(): void
    {
        $response = $this->getJson('/api/my-tickets');
        $this->assertTrue(in_array($response->status(), [401, 429], true));
    }

    public function test_my_tickets_returns_only_user_tickets(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        $this->seedTicket($user);
        $this->seedTicket($user);
        $this->seedTicket($other);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/my-tickets');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    public function test_my_tickets_filter_upcoming(): void
    {
        $user = $this->makeUser();
        $this->seedTicket($user, 'future');
        $this->seedTicket($user, 'past');

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/my-tickets?filter=upcoming');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_my_tickets_filter_past(): void
    {
        $user = $this->makeUser();
        $this->seedTicket($user, 'future');
        $this->seedTicket($user, 'past');

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/my-tickets?filter=past');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_my_tickets_search(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/my-tickets?search=' . $ticket->ticket_id);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    public function test_my_tickets_pagination(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 5; $i++) {
            $this->seedTicket($user);
        }

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/my-tickets?per_page=2&page=1');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertEquals(3, $response->json('meta.last_page'));
    }

    // ------------------------------------------------------------------
    // GET /api/users/me/dashboard-overview
    // ------------------------------------------------------------------

    public function test_dashboard_overview_requires_authentication(): void
    {
        $response = $this->getJson('/api/users/me/dashboard-overview');
        $this->assertTrue(in_array($response->status(), [401, 429], true));
    }

    public function test_dashboard_overview_returns_correct_metrics(): void
    {
        $user = $this->makeUser();
        $this->seedTicket($user, 'future');
        $this->seedTicket($user, 'future');
        $this->seedTicket($user, 'past');

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/users/me/dashboard-overview');

        $response->assertOk()
            ->assertJsonPath('data.totalTickets', 3)
            ->assertJsonPath('data.upcomingTickets', 2)
            ->assertJsonPath('data.pastTickets', 1)
            ->assertJsonStructure([
                'data' => ['totalTickets', 'upcomingTickets', 'pastTickets', 'checkedIn', 'nextUpcomingEvent', 'recentActivity'],
            ]);
    }

    public function test_dashboard_overview_next_upcoming_event(): void
    {
        $user = $this->makeUser();
        $this->seedTicket($user, 'future');

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/users/me/dashboard-overview');

        $response->assertOk();
        $this->assertNotNull($response->json('data.nextUpcomingEvent'));
    }

    public function test_dashboard_overview_no_tickets(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/users/me/dashboard-overview');

        $response->assertOk()
            ->assertJsonPath('data.totalTickets', 0)
            ->assertJsonPath('data.nextUpcomingEvent', null);
    }

    // ------------------------------------------------------------------
    // GET/PATCH /api/users/me/dashboard-preferences
    // ------------------------------------------------------------------

    public function test_dashboard_preferences_requires_authentication(): void
    {
        // The bearer middleware protects this route - should return 401 or 429 if rate limited
        $response = $this->getJson('/api/users/me/dashboard-preferences');
        $this->assertTrue(in_array($response->status(), [401, 429], true));
    }

    public function test_dashboard_preferences_returns_defaults(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/users/me/dashboard-preferences');

        $response->assertOk()
            ->assertJsonPath('data.default_ticket_filter', 'all')
            ->assertJsonPath('data.default_date_range', '30days');
    }

    public function test_dashboard_preferences_update(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/users/me/dashboard-preferences', [
            'default_ticket_filter' => 'upcoming',
            'default_date_range' => '7days',
            'show_recommendations' => true,
            'show_activity_feed' => true,
            'auto_refresh_enabled' => false,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.default_ticket_filter', 'upcoming')
            ->assertJsonPath('data.default_date_range', '7days');

        $this->assertDatabaseHas('user_dashboard_preferences', [
            'user_id' => $user->id,
            'default_ticket_filter' => 'upcoming',
        ]);
    }

    public function test_dashboard_preferences_rejects_invalid_filter(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')->patchJson('/api/users/me/dashboard-preferences', [
            'default_ticket_filter' => 'invalid',
        ]);

        $response->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // GET /api/tickets/:ticketId/details
    // ------------------------------------------------------------------

    public function test_ticket_details_requires_authentication(): void
    {
        // The bearer middleware protects this route - should return 401 or 429 if rate limited
        $response = $this->getJson('/api/tickets/some-id/details');
        $this->assertTrue(in_array($response->status(), [401, 429], true));
    }

    public function test_ticket_details_returns_full_info(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        DeliveryEvent::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'event_id' => $ticket->event_id,
            'channel' => 'email',
            'status' => 'delivered',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/tickets/' . $ticket->id . '/details');

        $response->assertOk()
            ->assertJsonPath('data.id', $ticket->id)
            ->assertJsonStructure([
                'data' => ['id', 'ticket_id', 'status', 'tier_name', 'event', 'delivery_history'],
            ]);
        $this->assertCount(1, $response->json('data.delivery_history'));
    }

    public function test_ticket_details_requires_ownership(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $ticket = $this->seedTicket($owner);

        $response = $this->actingAs($other, 'sanctum')->getJson('/api/tickets/' . $ticket->id . '/details');

        $response->assertForbidden();
    }

    public function test_ticket_details_returns_404_for_nonexistent(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/tickets/nonexistent-id/details');

        $response->assertNotFound();
    }
}
