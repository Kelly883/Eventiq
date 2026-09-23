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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
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

    private function seedTicket(User $user, ?string $eventStartDate = null, ?string $eventEndDate = null, string $ticketStatus = 'valid'): Ticket
    {
        $organizer = $user->organizer ?? $user->organizer()->create(['displayName' => $user->name ?? 'Test User']);

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'status' => 'published',
            'is_public' => true,
            'start_datetime' => $eventStartDate ?? now()->addDays(7)->toDateTimeString(),
            'end_datetime' => $eventEndDate ?? now()->addDays(8)->toDateTimeString(),
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
            'payment_intent_id' => 'pi_' . (string) Str::uuid(),
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
    // GET /api/my-tickets
    // ------------------------------------------------------------------

    public function test_my_tickets_requires_authentication(): void
    {
        $this->getJson('/api/my-tickets')->assertUnauthorized();
    }

    public function test_my_tickets_returns_only_authenticated_users_tickets(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();

        $userTicket1 = $this->seedTicket($user);
        $userTicket2 = $this->seedTicket($user);
        $otherTicket = $this->seedTicket($other);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/my-tickets');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($userTicket1->id, $ids);
        $this->assertContains($userTicket2->id, $ids);
        $this->assertNotContains($otherTicket->id, $ids);
    }

    public function test_my_tickets_filter_upcoming(): void
    {
        $user = $this->makeUser();
        $upcoming = $this->seedTicket($user, now()->addDays(7)->toDateTimeString());
        $past = $this->seedTicket($user, now()->subDays(7)->toDateTimeString(), now()->subDays(6)->toDateTimeString());

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/my-tickets?filter=upcoming');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($upcoming->id, $ids);
        $this->assertNotContains($past->id, $ids);
    }

    public function test_my_tickets_filter_past(): void
    {
        $user = $this->makeUser();
        $upcoming = $this->seedTicket($user, now()->addDays(7)->toDateTimeString());
        $past = $this->seedTicket($user, now()->subDays(7)->toDateTimeString(), now()->subDays(6)->toDateTimeString());

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/my-tickets?filter=past');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($past->id, $ids);
        $this->assertNotContains($upcoming->id, $ids);
    }

    public function test_my_tickets_filter_all_returns_everything(): void
    {
        $user = $this->makeUser();
        $upcoming = $this->seedTicket($user, now()->addDays(7)->toDateTimeString());
        $past = $this->seedTicket($user, now()->subDays(7)->toDateTimeString(), now()->subDays(6)->toDateTimeString());

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/my-tickets?filter=all');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($upcoming->id, $ids);
        $this->assertContains($past->id, $ids);
    }

    public function test_my_tickets_search_by_event_title(): void
    {
        $user = $this->makeUser();
        $matching = $this->seedTicket($user);
        $matching->event->update(['title' => 'Special Unique Concert XYZ']);

        $other = $this->seedTicket($user);
        $other->event->update(['title' => 'Regular Event']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/my-tickets?search=Special Unique Concert');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($matching->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_my_tickets_search_by_ticket_id(): void
    {
        $user = $this->makeUser();
        $ticket1 = $this->seedTicket($user);
        $ticket2 = $this->seedTicket($user);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/my-tickets?search=' . $ticket1->ticket_id);

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->toArray();
        $this->assertContains($ticket1->id, $ids);
        $this->assertNotContains($ticket2->id, $ids);
    }

    public function test_my_tickets_pagination(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 25; $i++) {
            $this->seedTicket($user);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/my-tickets?per_page=10&page=2');

        $response->assertOk();
        $this->assertEquals(2, $response->json('meta.current_page'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(3, $response->json('meta.last_page'));
        $this->assertCount(10, $response->json('data'));
    }

    public function test_my_tickets_invalid_filter_rejected(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/my-tickets?filter=invalid')
            ->assertStatus(422);
    }

    public function test_my_tickets_empty_result(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/my-tickets');

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
        $this->assertEquals(0, $response->json('meta.total'));
    }

    // ------------------------------------------------------------------
    // GET /api/users/me/dashboard-overview
    // ------------------------------------------------------------------

    public function test_dashboard_overview_requires_authentication(): void
    {
        $this->getJson('/api/users/me/dashboard-overview')->assertUnauthorized();
    }

    public function test_dashboard_overview_returns_correct_metrics(): void
    {
        $user = $this->makeUser();

        $upcoming1 = $this->seedTicket($user, now()->addDays(7)->toDateTimeString());
        $upcoming2 = $this->seedTicket($user, now()->addDays(14)->toDateTimeString());
        $past = $this->seedTicket($user, now()->subDays(7)->toDateTimeString(), now()->subDays(6)->toDateTimeString());

        DB::table('tickets')->where('id', $upcoming1->id)->update(['checked_in' => 1]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/dashboard-overview');

        $response->assertOk()
            ->assertJsonPath('data.totalTickets', 3)
            ->assertJsonPath('data.upcomingTickets', 2)
            ->assertJsonPath('data.pastTickets', 1)
            ->assertJsonPath('data.checkedIn', 1);
    }

    public function test_dashboard_overview_next_upcoming_event_is_first_by_date(): void
    {
        $user = $this->makeUser();

        // Create tickets in non-chronological order
        $later = $this->seedTicket($user, now()->addDays(30)->toDateTimeString());
        $earlier = $this->seedTicket($user, now()->addDays(3)->toDateTimeString());

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/dashboard-overview');

        $response->assertOk();
        $this->assertNotNull($response->json('data.nextUpcomingEvent'));
        // The earliest upcoming event should be the one 3 days from now
        $this->assertEquals(
            $earlier->event->title,
            $response->json('data.nextUpcomingEvent.title')
        );
    }

    public function test_dashboard_overview_recent_activity_shows_last_5(): void
    {
        $user = $this->makeUser();

        for ($i = 0; $i < 10; $i++) {
            $this->seedTicket($user, now()->addDays($i + 1)->toDateTimeString());
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/dashboard-overview');

        $response->assertOk();
        $this->assertCount(5, $response->json('data.recentActivity'));
    }

    public function test_dashboard_overview_empty_when_no_tickets(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/dashboard-overview');

        $response->assertOk()
            ->assertJsonPath('data.totalTickets', 0)
            ->assertJsonPath('data.upcomingTickets', 0)
            ->assertJsonPath('data.pastTickets', 0)
            ->assertJsonPath('data.checkedIn', 0)
            ->assertJsonPath('data.nextUpcomingEvent', null)
            ->assertJsonPath('data.recentActivity', []);
    }

    // ------------------------------------------------------------------
    // GET /api/users/me/dashboard-preferences
    // ------------------------------------------------------------------

    public function test_dashboard_preferences_requires_authentication(): void
    {
        $this->getJson('/api/users/me/dashboard-preferences')->assertUnauthorized();
    }

    public function test_dashboard_preferences_returns_defaults(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/dashboard-preferences');

        $response->assertOk()
            ->assertJsonPath('data.default_ticket_filter', 'all')
            ->assertJsonPath('data.default_date_range', '30days')
            ->assertJsonPath('data.show_recommendations', true)
            ->assertJsonPath('data.show_activity_feed', true)
            ->assertJsonPath('data.auto_refresh_enabled', true);
    }

    // ------------------------------------------------------------------
    // PATCH /api/users/me/dashboard-preferences
    // ------------------------------------------------------------------

    public function test_dashboard_preferences_patch_requires_authentication(): void
    {
        $this->patchJson('/api/users/me/dashboard-preferences', [])->assertUnauthorized();
    }

    public function test_dashboard_preferences_patch_updates_preferences(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/users/me/dashboard-preferences', [
                'default_ticket_filter' => 'upcoming',
                'default_date_range' => '7days',
                'show_recommendations' => false,
                'show_activity_feed' => false,
                'auto_refresh_enabled' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.default_ticket_filter', 'upcoming')
            ->assertJsonPath('data.default_date_range', '7days')
            ->assertJsonPath('data.show_recommendations', false)
            ->assertJsonPath('data.show_activity_feed', false)
            ->assertJsonPath('data.auto_refresh_enabled', false);

        // Verify persistence
        $response2 = $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/dashboard-preferences');

        $response2->assertOk()
            ->assertJsonPath('data.default_ticket_filter', 'upcoming')
            ->assertJsonPath('data.default_date_range', '7days')
            ->assertJsonPath('data.show_recommendations', false)
            ->assertJsonPath('data.show_activity_feed', false)
            ->assertJsonPath('data.auto_refresh_enabled', false);
    }

    public function test_dashboard_preferences_patch_partial_update(): void
    {
        $user = $this->makeUser();

        // Set initial values
        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/users/me/dashboard-preferences', [
                'default_ticket_filter' => 'upcoming',
            ]);

        // Update only one field
        $response = $this->actingAs($user, 'sanctum')
            ->patchJson('/api/users/me/dashboard-preferences', [
                'show_recommendations' => false,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.default_ticket_filter', 'upcoming')
            ->assertJsonPath('data.show_recommendations', false)
            ->assertJsonPath('data.show_activity_feed', true);
    }

    public function test_dashboard_preferences_patch_invalid_filter_rejected(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/users/me/dashboard-preferences', [
                'default_ticket_filter' => 'invalid',
            ])
            ->assertStatus(422);
    }

    public function test_dashboard_preferences_patch_invalid_date_range_rejected(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/users/me/dashboard-preferences', [
                'default_date_range' => 'invalid',
            ])
            ->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // GET /api/tickets/:ticketId/details
    // ------------------------------------------------------------------

    public function test_ticket_details_requires_authentication(): void
    {
        $this->getJson('/api/tickets/some-id/details')->assertUnauthorized();
    }

    public function test_ticket_details_returns_full_info(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tickets/' . $ticket->id . '/details');

        $response->assertOk()
            ->assertJsonPath('data.id', $ticket->id)
            ->assertJsonPath('data.ticket_id', $ticket->ticket_id)
            ->assertJsonPath('data.status', 'valid')
            ->assertJsonStructure([
                'data' => [
                    'id', 'ticket_id', 'status', 'tier_name',
                    'attendee_name', 'attendee_email', 'qr_code_data',
                    'checked_in', 'checked_in_at', 'created_at',
                    'event' => ['id', 'title', 'start_datetime', 'end_datetime', 'venue_name', 'venue_address'],
                    'delivery_history',
                ],
            ]);
    }

    public function test_ticket_details_includes_delivery_history(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        DeliveryEvent::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'channel' => 'email',
            'status' => 'delivered',
            'recipient' => $user->email,
        ]);

        DeliveryEvent::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'channel' => 'email',
            'status' => 'failed',
            'recipient' => $user->email,
            'attempt_count' => 1,
            'max_attempts' => 3,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tickets/' . $ticket->id . '/details');

        $response->assertOk();
        $history = $response->json('data.delivery_history');
        $this->assertCount(2, $history);
    }

    public function test_ticket_details_returns_404_for_nonexistent(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tickets/nonexistent-id/details')
            ->assertNotFound();
    }

    public function test_ticket_details_forbidden_for_other_user(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $ticket = $this->seedTicket($owner);

        $this->actingAs($other, 'sanctum')
            ->getJson('/api/tickets/' . $ticket->id . '/details')
            ->assertForbidden();
    }

    public function test_ticket_details_admin_can_view(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeUser('admin');
        $ticket = $this->seedTicket($owner);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/tickets/' . $ticket->id . '/details')
            ->assertOk()
            ->assertJsonPath('data.id', $ticket->id);
    }

    public function test_ticket_details_delivery_history_limited_to_50(): void
    {
        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        for ($i = 0; $i < 60; $i++) {
            DeliveryEvent::factory()->create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'channel' => 'email',
                'status' => 'failed',
                'recipient' => $user->email,
            ]);
        }

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/tickets/' . $ticket->id . '/details');

        $response->assertOk();
        $this->assertCount(50, $response->json('data.delivery_history'));
    }

    // ------------------------------------------------------------------
    // Rate limiting
    // ------------------------------------------------------------------

    public function test_my_tickets_rate_limited(): void
    {
        RateLimiter::for('dashboard-metrics', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('127.0.0.1'));

        $user = $this->makeUser();

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/my-tickets')
                ->assertOk();
        }

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/my-tickets')
            ->assertStatus(429);
    }

    public function test_dashboard_overview_rate_limited(): void
    {
        RateLimiter::for('dashboard-metrics', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('127.0.0.1'));

        $user = $this->makeUser();

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/users/me/dashboard-overview')
                ->assertOk();
        }

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/users/me/dashboard-overview')
            ->assertStatus(429);
    }

    public function test_ticket_details_rate_limited(): void
    {
        RateLimiter::for('dashboard-metrics', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('127.0.0.1'));

        $user = $this->makeUser();
        $ticket = $this->seedTicket($user);

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($user, 'sanctum')
                ->getJson('/api/tickets/' . $ticket->id . '/details')
                ->assertOk();
        }

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/tickets/' . $ticket->id . '/details')
            ->assertStatus(429);
    }

    // ------------------------------------------------------------------
    // Cross-endpoint security
    // ------------------------------------------------------------------

    public function test_admin_can_view_other_users_tickets(): void
    {
        $owner = $this->makeUser();
        $admin = $this->makeUser('admin');
        $ticket = $this->seedTicket($owner);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/tickets/' . $ticket->id . '/details')
            ->assertOk();
    }

    public function test_non_owner_non_admin_cannot_view_ticket_details(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $ticket = $this->seedTicket($owner);

        $this->actingAs($other, 'sanctum')
            ->getJson('/api/tickets/' . $ticket->id . '/details')
            ->assertForbidden();
    }
}
