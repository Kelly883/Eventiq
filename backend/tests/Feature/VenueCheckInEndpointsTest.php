<?php

namespace Tests\Feature;

use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Ticket;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class VenueCheckInEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::for('venue-check-in-search', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('venue-check-in-stats', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('venue-check-in-export', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('venue-bulk-check-in', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('venue-detect-duplicate', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
        RateLimiter::for('venue-offline-sync', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
    }

    protected function tearDown(): void
    {
        RateLimiter::for('venue-check-in-search', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by('127.0.0.1'));
        RateLimiter::for('venue-check-in-stats', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by('127.0.0.1'));
        RateLimiter::for('venue-check-in-export', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('127.0.0.1'));
        RateLimiter::for('venue-bulk-check-in', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by('127.0.0.1'));
        RateLimiter::for('venue-detect-duplicate', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by('127.0.0.1'));
        RateLimiter::for('venue-offline-sync', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(10)->by('127.0.0.1'));
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
        } elseif ($role === 'venue_staff') {
            $staffRole = Role::firstOrCreate(['name' => 'venue_staff'], ['description' => 'Venue Staff', 'isSystemRole' => true]);
            if (!$user->roles()->where('name', 'venue_staff')->exists()) {
                $user->roles()->attach($staffRole);
            }
        }

        return $user;
    }

    private function seedEventWithTickets(User $owner, int $ticketCount = 5): array
    {
        $organizer = $owner->organizer ?? $owner->organizer()->create(['displayName' => $owner->name ?? 'Test User']);

        $event = Event::factory()->create([
            'organizer_id' => $organizer->id,
            'status' => 'published',
            'is_public' => true,
            'start_datetime' => now()->addDays(7)->toDateTimeString(),
            'end_datetime' => now()->addDays(8)->toDateTimeString(),
        ]);

        $tier = TicketTier::factory()->create([
            'event_id' => $event->id,
            'status' => 'published',
            'quantity' => 100,
            'sold_count' => 0,
            'price' => 15000.00,
        ]);

        $staff = $this->makeUser('venue_staff');
        $event->venueStaff()->attach($staff->id);

        $tickets = [];
        for ($i = 0; $i < $ticketCount; $i++) {
            $order = Order::create([
                'user_id' => $owner->id,
                'event_id' => $event->id,
                'total_amount' => 15000.00,
                'currency' => 'NGN',
                'status' => 'completed',
                'payment_gateway' => 'paystack',
                'payment_intent_id' => 'pi_' . (string) Str::uuid(),
            ]);

            $tickets[] = Ticket::factory()->create([
                'user_id' => $owner->id,
                'event_id' => $event->id,
                'ticket_tier_id' => $tier->id,
                'order_id' => $order->id,
                'status' => 'valid',
                'attendee_name' => 'Attendee ' . $i,
                'attendee_email' => "attendee{$i}@example.com",
                'qr_code_generated_at' => null,
                'qr_code_expires_at' => null,
            ]);
        }

        return ['event' => $event, 'tickets' => $tickets, 'staff' => $staff];
    }

    // ------------------------------------------------------------------
    // GET /api/venue/check-in/search
    // ------------------------------------------------------------------

    public function test_search_requires_authentication(): void
    {
        $this->getJson('/api/venue/check-in/search?event_id=some-id&query=test')
            ->assertUnauthorized();
    }

    public function test_search_requires_venue_staff_role(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/venue/check-in/search?event_id=' . $seed['event']->id . '&query=attendee')
            ->assertForbidden();
    }

    public function test_search_returns_matching_tickets_by_ticket_id(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user);

        $ticket = $seed['tickets'][0];

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/search?event_id=' . $seed['event']->id . '&query=' . $ticket->ticket_id);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $results = $response->json('data.results');
        $this->assertNotEmpty($results);
        $this->assertEquals($ticket->id, $results[0]['ticket_id']);
    }

    public function test_search_returns_matching_tickets_by_email(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/search?event_id=' . $seed['event']->id . '&query=attendee0@');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $results = $response->json('data.results');
        $this->assertNotEmpty($results);
    }

    public function test_search_returns_matching_tickets_by_name(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/search?event_id=' . $seed['event']->id . '&query=Attendee');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $results = $response->json('data.results');
        $this->assertCount(5, $results);
    }

    public function test_search_results_limited_to_10_items(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 15);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/search?event_id=' . $seed['event']->id . '&query=Attendee');

        $response->assertOk();
        $this->assertLessThanOrEqual(10, count($response->json('data.results')));
    }

    public function test_search_rejects_short_query(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user);

        $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/search?event_id=' . $seed['event']->id . '&query=a')
            ->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // POST /api/venue/check-in/bulk
    // ------------------------------------------------------------------

    public function test_bulk_check_in_requires_authentication(): void
    {
        $this->postJson('/api/venue/check-in/bulk', [])
            ->assertUnauthorized();
    }

    public function test_bulk_check_in_requires_venue_staff_role(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/venue/check-in/bulk', [
                'event_id' => (string) $seed['event']->id,
                'ticket_ids' => [$seed['tickets'][0]->id],
            ])
            ->assertForbidden();
    }

    public function test_bulk_check_in_marks_tickets_as_checked_in(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 5);

        $ticketIds = array_map(fn ($t) => $t->id, $seed['tickets']);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/venue/check-in/bulk', [
                'event_id' => (string) $seed['event']->id,
                'ticket_ids' => $ticketIds,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.success_count', 5)
            ->assertJsonPath('data.failure_count', 0);

        foreach ($seed['tickets'] as $ticket) {
            $this->assertEquals('checked_in', $ticket->fresh()->status);
        }
    }

    public function test_bulk_check_in_is_idempotent(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 3);

        $ticketIds = array_map(fn ($t) => $t->id, $seed['tickets']);

        // First bulk check-in
        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/venue/check-in/bulk', [
                'event_id' => (string) $seed['event']->id,
                'ticket_ids' => $ticketIds,
            ])
            ->assertOk();

        // Second bulk check-in (same tickets)
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/venue/check-in/bulk', [
                'event_id' => (string) $seed['event']->id,
                'ticket_ids' => $ticketIds,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.success_count', 3);

        // Verify no double-counting
        foreach ($seed['tickets'] as $ticket) {
            $this->assertEquals('checked_in', $ticket->fresh()->status);
        }
    }

    public function test_bulk_check_in_with_duplicate_ids_deduplicates(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 2);

        $ticketIds = [$seed['tickets'][0]->id, $seed['tickets'][0]->id, $seed['tickets'][1]->id];

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/venue/check-in/bulk', [
                'event_id' => (string) $seed['event']->id,
                'ticket_ids' => $ticketIds,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.success_count', 3);
    }

    public function test_bulk_check_in_returns_per_ticket_results(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 3);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/venue/check-in/bulk', [
                'event_id' => (string) $seed['event']->id,
                'ticket_ids' => [$seed['tickets'][0]->id, 'nonexistent-id', $seed['tickets'][1]->id],
            ]);

        $response->assertOk();
        $results = $response->json('data.results');
        $this->assertCount(3, $results);
    }

    // ------------------------------------------------------------------
    // GET /api/venue/check-in/stats
    // ------------------------------------------------------------------

    public function test_stats_requires_authentication(): void
    {
        $this->getJson('/api/venue/check-in/stats/some-event')
            ->assertUnauthorized();
    }

    public function test_stats_requires_venue_staff_role(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/venue/check-in/stats/' . $seed['event']->id)
            ->assertForbidden();
    }

    public function test_stats_returns_correct_counts(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 10);

        // Check in 4 tickets
        foreach (array_slice($seed['tickets'], 0, 4) as $ticket) {
            $checkInResponse = $this->actingAs($seed['staff'], 'sanctum')
                ->postJson('/api/tickets/' . $ticket->id . '/check-in', []);
            $checkInResponse->assertOk();
        }

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/stats/' . $seed['event']->id);

        $response->assertOk()
            ->assertJsonPath('data.total_capacity', 10)
            ->assertJsonPath('data.total_checked_in', 4)
            ->assertJsonPath('data.total_remaining', 6)
            ->assertJsonPath('data.check_in_rate', 40);
    }

    public function test_stats_incremental_with_last_update_at(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 5);

        // Check in 2 tickets
        foreach (array_slice($seed['tickets'], 0, 2) as $ticket) {
            $checkInResponse = $this->actingAs($seed['staff'], 'sanctum')
                ->postJson('/api/tickets/' . $ticket->id . '/check-in', []);
            $checkInResponse->assertOk();
        }

        $lastUpdate = now()->toDateTimeString();

        // Check in 1 more ticket
        sleep(1);
        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/tickets/' . $seed['tickets'][2]->id . '/check-in', [])
            ->assertOk();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/stats/' . $seed['event']->id . '?lastUpdateAt=' . $lastUpdate);

        $response->assertOk()
            ->assertJsonPath('data.total_checked_in', 1);
    }

    // ------------------------------------------------------------------
    // POST /api/venue/check-in/detect-duplicate
    // ------------------------------------------------------------------

    public function test_detect_duplicate_requires_authentication(): void
    {
        $this->postJson('/api/venue/check-in/detect-duplicate', [])
            ->assertUnauthorized();
    }

    public function test_detect_duplicate_requires_venue_staff_role(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/venue/check-in/detect-duplicate', [
                'event_id' => (string) $seed['event']->id,
                'ticket_id' => $seed['tickets'][0]->id,
            ])
            ->assertForbidden();
    }

    public function test_detect_duplicate_detects_already_checked_in_ticket(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 3);

        // Check in first ticket
        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/tickets/' . $seed['tickets'][0]->id . '/check-in', []);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/venue/check-in/detect-duplicate', [
                'event_id' => (string) $seed['event']->id,
                'ticket_id' => $seed['tickets'][0]->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.is_duplicate', true);

        $this->assertNotNull($response->json('data.previous_check_in_at'));
    }

    public function test_detect_duplicate_returns_false_for_valid_ticket(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 3);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/venue/check-in/detect-duplicate', [
                'event_id' => (string) $seed['event']->id,
                'ticket_id' => $seed['tickets'][0]->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.is_duplicate', false)
            ->assertJsonPath('data.risk_level', 'low');
    }

    // ------------------------------------------------------------------
    // POST /api/venue/check-in/offline-sync
    // ------------------------------------------------------------------

    public function test_offline_sync_requires_authentication(): void
    {
        $this->postJson('/api/venue/check-in/offline-sync', [])
            ->assertUnauthorized();
    }

    public function test_offline_sync_requires_venue_staff_role(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/venue/check-in/offline-sync', [
                'event_id' => (string) $seed['event']->id,
                'local_check_ins' => [],
            ])
            ->assertForbidden();
    }

    public function test_offline_sync_syncs_local_check_ins(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 3);

        $localCheckIns = [
            ['ticket_id' => $seed['tickets'][0]->id, 'checked_in_at' => now()->subMinutes(5)->toDateTimeString()],
            ['ticket_id' => $seed['tickets'][1]->id, 'checked_in_at' => now()->subMinutes(3)->toDateTimeString()],
        ];

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/venue/check-in/offline-sync', [
                'event_id' => (string) $seed['event']->id,
                'local_check_ins' => $localCheckIns,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.synced', 2)
            ->assertJsonPath('data.conflicts', 0);

        $this->assertEquals('checked_in', $seed['tickets'][0]->fresh()->status);
        $this->assertEquals('checked_in', $seed['tickets'][1]->fresh()->status);
        $this->assertEquals('synced', $seed['tickets'][0]->fresh()->sync_status);
    }

    public function test_offline_sync_handles_conflicts(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 3);

        // Manually set server check-in using DB::table to ensure fields are persisted
        $serverCheckInAt = now()->subMinute();
        \Illuminate\Support\Facades\DB::table('tickets')
            ->where('id', $seed['tickets'][0]->id)
            ->update([
                'status' => 'checked_in',
                'checked_in' => 1,
                'checked_in_at' => $serverCheckInAt,
                'checked_in_by' => $seed['staff']->id,
                'sync_status' => 'synced',
            ]);

        // Try to sync an older local check-in for the same ticket
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/venue/check-in/offline-sync', [
                'event_id' => (string) $seed['event']->id,
                'local_check_ins' => [
                    ['ticket_id' => $seed['tickets'][0]->id, 'checked_in_at' => now()->subMinutes(10)->toDateTimeString()],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.synced', 0)
            ->assertJsonPath('data.conflicts', 1);
    }

    public function test_offline_sync_returns_server_state(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 3);

        // Check in 2 tickets on server
        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/tickets/' . $seed['tickets'][0]->id . '/check-in', [])
            ->assertOk();
        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/tickets/' . $seed['tickets'][1]->id . '/check-in', [])
            ->assertOk();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/venue/check-in/offline-sync', [
                'event_id' => (string) $seed['event']->id,
                'local_check_ins' => [
                    ['ticket_id' => $seed['tickets'][2]->id, 'checked_in_at' => now()->subMinutes(2)->toDateTimeString()],
                ],
            ]);

        $response->assertOk();
        $serverCheckIns = $response->json('data.server_check_ins');
        $this->assertCount(3, $serverCheckIns);
    }

    // ------------------------------------------------------------------
    // GET /api/venue/check-in/export
    // ------------------------------------------------------------------

    public function test_export_requires_authentication(): void
    {
        $this->getJson('/api/venue/check-in/export/some-event')
            ->assertUnauthorized();
    }

    public function test_export_requires_venue_staff_role(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/venue/check-in/export/' . $seed['event']->id)
            ->assertForbidden();
    }

    public function test_export_returns_csv_by_default(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 3);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/export/' . $seed['event']->id);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_export_returns_json_when_requested(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 3);

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/export/' . $seed['event']->id . '?format=json');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.format', 'json');
    }

    public function test_export_respects_time_range_filters(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 3);

        // Check in tickets at different times
        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/tickets/' . $seed['tickets'][0]->id . '/check-in', [])
            ->assertOk();

        sleep(1);

        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/tickets/' . $seed['tickets'][1]->id . '/check-in', [])
            ->assertOk();

        $startTime = now()->subMinutes(5)->toDateTimeString();
        $endTime = now()->toDateTimeString();

        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/export/' . $seed['event']->id . '?format=json&start_time=' . $startTime . '&end_time=' . $endTime);

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.total_records'));
    }

    public function test_export_include_no_shows(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 5);

        // Check in only 2 tickets
        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/tickets/' . $seed['tickets'][0]->id . '/check-in', [])
            ->assertOk();
        $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/tickets/' . $seed['tickets'][1]->id . '/check-in', [])
            ->assertOk();

        // Without includeNoShows - only checked_in tickets
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/export/' . $seed['event']->id . '?format=json');

        $this->assertEquals(2, $response->json('data.total_records'));

        // With includeNoShows - all tickets
        $response = $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/export/' . $seed['event']->id . '?format=json&include_no_shows=1');

        $this->assertEquals(5, count($response->json('data.records')));
    }

    // ------------------------------------------------------------------
    // Full flow test
    // ------------------------------------------------------------------

    public function test_full_check_in_flow(): void
    {
        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user, 5);
        $staff = $seed['staff'];

        // 1. Search for a ticket
        $searchResponse = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/venue/check-in/search?event_id=' . $seed['event']->id . '&query=Attendee');
        $searchResponse->assertOk();
        $this->assertCount(5, $searchResponse->json('data.results'));

        // 2. Bulk check in 3 tickets
        $ticketIds = array_map(fn ($t) => $t->id, array_slice($seed['tickets'], 0, 3));
        $bulkResponse = $this->actingAs($seed['staff'], 'sanctum')
            ->postJson('/api/venue/check-in/bulk', [
                'event_id' => (string) $seed['event']->id,
                'ticket_ids' => $ticketIds,
            ]);
        $bulkResponse->assertOk()->assertJsonPath('data.success_count', 3);

        // 3. Get stats
        $statsResponse = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/venue/check-in/stats/' . $seed['event']->id);
        $statsResponse->assertOk()
            ->assertJsonPath('data.total_checked_in', 3)
            ->assertJsonPath('data.total_capacity', 5)
            ->assertJsonPath('data.check_in_rate', 60);

        // 4. Try checking in a duplicate
        $dupResponse = $this->actingAs($staff, 'sanctum')
            ->postJson('/api/venue/check-in/detect-duplicate', [
                'event_id' => (string) $seed['event']->id,
                'ticket_id' => $seed['tickets'][0]->id,
            ]);
        $dupResponse->assertOk()->assertJsonPath('data.is_duplicate', true);

        // 5. Offline sync a new check-in
        $syncResponse = $this->actingAs($staff, 'sanctum')
            ->postJson('/api/venue/check-in/offline-sync', [
                'event_id' => (string) $seed['event']->id,
                'local_check_ins' => [
                    ['ticket_id' => $seed['tickets'][3]->id, 'checked_in_at' => now()->subMinutes(2)->toDateTimeString()],
                ],
            ]);
        $syncResponse->assertOk()->assertJsonPath('data.synced', 1);

        // 6. Export as CSV
        $exportResponse = $this->actingAs($staff, 'sanctum')
            ->getJson('/api/venue/check-in/export/' . $seed['event']->id);
        $exportResponse->assertOk();
    }

    // ------------------------------------------------------------------
    // Rate limiting
    // ------------------------------------------------------------------

    public function test_search_rate_limited(): void
    {
        RateLimiter::for('venue-check-in-search', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(20)->by('127.0.0.1'));

        $user = $this->makeUser();
        $seed = $this->seedEventWithTickets($user);

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($seed['staff'], 'sanctum')
                ->getJson('/api/venue/check-in/search?event_id=' . $seed['event']->id . '&query=Attendee')
                ->assertOk();
        }

        $this->actingAs($seed['staff'], 'sanctum')
            ->getJson('/api/venue/check-in/search?event_id=' . $seed['event']->id . '&query=Attendee')
            ->assertStatus(429);
    }
}
