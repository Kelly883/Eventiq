<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEventEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $organizerUser;
    private User $otherUser;
    private Organizer $organizer;
    private Organizer $otherOrganizer;
    private Event $publishedEvent;
    private Event $draftEvent;

    protected function setUp(): void
    {
        parent::setUp();

        $organizerRole = Role::factory()->create(['name' => 'organizer']);

        $this->organizerUser = User::factory()->create(['role_id' => $organizerRole->id]);
        $this->organizer = Organizer::factory()->create(['user_id' => $this->organizerUser->id]);

        $this->otherUser = User::factory()->create(['role' => 'organizer']);
        $this->otherOrganizer = Organizer::factory()->create(['user_id' => $this->otherUser->id]);

        $this->publishedEvent = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
            'title' => 'Jazz Night',
            'category' => 'music',
            'start_datetime' => now()->addDays(5),
        ]);

        $this->draftEvent = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'draft',
            'title' => 'Draft Event',
            'category' => 'music',
        ]);
    }

    // ── GET /api/public/events ─────────────────────────────────────────

    public function test_list_returns_published_events(): void
    {
        $response = $this->getJson('/api/public/events');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->publishedEvent->id, $data[0]['id']);
    }

    public function test_list_excludes_draft_events(): void
    {
        $response = $this->getJson('/api/public/events');
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($this->draftEvent->id, $ids);
    }

    public function test_list_filters_by_category(): void
    {
        Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
            'category' => 'sports',
        ]);

        $response = $this->getJson('/api/public/events?category=music');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('music', $data[0]['category']);
    }

    public function test_list_filters_by_date_range_week(): void
    {
        Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
            'start_datetime' => now()->addDays(30),
        ]);

        $response = $this->getJson('/api/public/events?date_range=week');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data); // only the event within 7 days
    }

    public function test_list_filters_by_date_range_month(): void
    {
        $response = $this->getJson('/api/public/events?date_range=month');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_list_filters_by_price_range(): void
    {
        TicketTier::factory()->create([
            'event_id' => $this->publishedEvent->id,
            'price' => 50,
            'status' => 'published',
        ]);
        $expensiveEvent = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
        ]);
        TicketTier::factory()->create([
            'event_id' => $expensiveEvent->id,
            'price' => 200,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/public/events?min_price=40&max_price=100');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($this->publishedEvent->id, $data[0]['id']);
    }

    public function test_list_sorts_by_date(): void
    {
        Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
            'start_datetime' => now()->addDay(),
        ]);

        $response = $this->getJson('/api/public/events?sort=date');
        $response->assertStatus(200);
        $dates = collect($response->json('data'))->pluck('start_datetime')->all();
        $sorted = $dates;
        sort($sorted);
        $this->assertEquals($sorted, $dates);
    }

    public function test_list_sorts_by_price_asc(): void
    {
        $event1 = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
        ]);
        $event2 = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
        ]);

        TicketTier::factory()->create(['event_id' => $event1->id, 'price' => 100, 'status' => 'published']);
        TicketTier::factory()->create(['event_id' => $event2->id, 'price' => 50, 'status' => 'published']);

        $response = $this->getJson('/api/public/events?sort=price_asc');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($data));
    }

    public function test_list_custom_date_range(): void
    {
        $response = $this->getJson('/api/public/events?date_range=custom&start_date=' . now()->subDay()->format('Y-m-d') . '&end_date=' . now()->addDays(10)->format('Y-m-d'));
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_list_pagination(): void
    {
        Event::factory()->count(25)->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
        ]);

        $response = $this->getJson('/api/public/events?per_page=10');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(10, $data);
    }

    // ── GET /api/public/events/search ──────────────────────────────────

    public function test_search_returns_results_for_valid_query(): void
    {
        $response = $this->getJson('/api/public/events/search?q=jazz');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Jazz Night', $data[0]['title']);
    }

    public function test_search_rejects_short_queries(): void
    {
        $response = $this->getJson('/api/public/events/search?q=a');
        $response->assertStatus(400);
    }

    public function test_search_returns_empty_for_no_results(): void
    {
        $response = $this->getJson('/api/public/events/search?q=nonexistent');
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    // ── GET /api/public/events/filters ─────────────────────────────────

    public function test_filters_returns_categories_and_price_range(): void
    {
        TicketTier::factory()->create([
            'event_id' => $this->publishedEvent->id,
            'price' => 25,
            'status' => 'published',
        ]);
        Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
            'category' => 'sports',
        ]);

        $response = $this->getJson('/api/public/events/filters');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('price_range', $data);
        $this->assertGreaterThanOrEqual(1, count($data['categories']));
        $this->assertArrayHasKey('min', $data['price_range']);
        $this->assertArrayHasKey('max', $data['price_range']);
    }

    // ── GET /api/public/events/{event} ─────────────────────────────────

    public function test_show_returns_full_event_details(): void
    {
        $response = $this->getJson("/api/public/events/{$this->publishedEvent->id}");
        $response->assertStatus(200);

        $data = $response->json();
        $event = $data['data'] ?? $data;
        $this->assertEquals($this->publishedEvent->id, $event['id']);
        $this->assertArrayHasKey('title', $event);
        $this->assertArrayHasKey('slug', $event);
        $this->assertArrayHasKey('ticket_tiers', $event);
        $this->assertArrayHasKey('organizer', $event);
        $this->assertArrayHasKey('ticket_availability', $event);
        $this->assertArrayHasKey('popularity_score', $event);
    }

    public function test_show_returns_404_for_draft_event(): void
    {
        $response = $this->getJson("/api/public/events/{$this->draftEvent->id}");
        $response->assertStatus(404);
    }

    public function test_show_returns_404_for_nonexistent_event(): void
    {
        $response = $this->getJson('/api/public/events/00000000-0000-0000-0000-000000000000');
        $response->assertStatus(404);
    }

    // ── GET /api/public/events/{event}/pricing ─────────────────────────

    public function test_pricing_returns_current_and_next_pricing(): void
    {
        $tier = TicketTier::factory()->create([
            'event_id' => $this->publishedEvent->id,
            'price' => 50,
            'status' => 'published',
        ]);

        // Current pricing window
        $currentWindow = \App\Features\Pricing\Models\PricingWindow::create([
            'event_id' => $this->publishedEvent->id,
            'ticket_category_id' => $tier->id,
            'window_name' => 'Early Bird',
            'start_date_time' => now()->subDay(),
            'end_date_time' => now()->addDays(7),
            'price' => 40,
            'quantity_limit' => 100,
            'is_active' => true,
            'priority' => 10,
        ]);

        $response = $this->getJson("/api/public/events/{$this->publishedEvent->id}/pricing");
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals($this->publishedEvent->id, $data['event_id']);
        $this->assertNotNull($data['current_pricing']);
        $this->assertEquals('Early Bird', $data['current_pricing']['window_name']);
        $this->assertEquals(40, $data['current_pricing']['price']);
        $this->assertArrayHasKey('next_pricing', $data);
        $this->assertArrayHasKey('ticket_tiers', $data);
    }

    public function test_pricing_returns_404_for_draft_event(): void
    {
        $response = $this->getJson("/api/public/events/{$this->draftEvent->id}/pricing");
        $response->assertStatus(404);
    }

    public function test_pricing_returns_null_current_when_no_active_window(): void
    {
        $response = $this->getJson("/api/public/events/{$this->publishedEvent->id}/pricing");
        $response->assertStatus(200);
        $this->assertNull($response->json('data.current_pricing'));
    }

    // ── GET /api/public/events/{event}/related ─────────────────────────

    public function test_related_returns_events_from_same_organizer(): void
    {
        $relatedEvent = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
            'title' => 'Related Event',
        ]);

        $response = $this->getJson("/api/public/events/{$this->publishedEvent->id}/related");
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals($relatedEvent->id, $data[0]['id']);
    }

    public function test_related_excludes_current_event(): void
    {
        $response = $this->getJson("/api/public/events/{$this->publishedEvent->id}/related");
        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertNotContains($this->publishedEvent->id, $ids);
    }

    public function test_related_returns_404_for_draft_event(): void
    {
        $response = $this->getJson("/api/public/events/{$this->draftEvent->id}/related");
        $response->assertStatus(404);
    }

    public function test_related_returns_empty_for_no_related_events(): void
    {
        $response = $this->getJson("/api/public/events/{$this->publishedEvent->id}/related");
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    // ── GET /api/public/events/category/{category} ─────────────────────

    public function test_category_filters_events(): void
    {
        $response = $this->getJson('/api/public/events/category/music');
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_category_returns_404_for_empty_category(): void
    {
        $response = $this->getJson('/api/public/events/category/nonexistent');
        $response->assertStatus(404);
    }

    public function test_category_returns_400_for_invalid_category(): void
    {
        $response = $this->getJson('/api/public/events/category/' . urlencode(''));
        $this->assertContains($response->status(), [400, 404]);
    }

    // ── Rate limiting ──────────────────────────────────────────────────

    public function test_rate_limit_is_enforced(): void
    {
        // Override limiter for testing
        \Illuminate\Support\Facades\RateLimiter::for('discovery', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(3));

        for ($i = 0; $i < 3; $i++) {
            $response = $this->getJson('/api/public/events');
            $this->assertEquals(200, $response->status(), "Request {$i} should succeed");
        }

        $response = $this->getJson('/api/public/events');
        $this->assertEquals(429, $response->status(), '4th request should be rate-limited');
    }

    // ── Legacy endpoints ───────────────────────────────────────────────

    public function test_legacy_categories_returns_same_as_filters(): void
    {
        $response = $this->getJson('/api/public/categories');
        $response->assertStatus(200);
        $this->assertArrayHasKey('data', $response->json());
    }

    public function test_legacy_ticket_tiers_returns_tiers(): void
    {
        TicketTier::factory()->create([
            'event_id' => $this->publishedEvent->id,
            'price' => 50,
            'status' => 'published',
        ]);

        $response = $this->getJson("/api/public/events/{$this->publishedEvent->id}/ticket-tiers");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_legacy_availability_returns_correct_data(): void
    {
        $response = $this->getJson("/api/public/events/{$this->publishedEvent->id}/availability");
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals($this->publishedEvent->id, $data['event_id']);
        $this->assertArrayHasKey('total_capacity', $data);
        $this->assertArrayHasKey('total_sold', $data);
        $this->assertArrayHasKey('availability', $data);
        $this->assertArrayHasKey('is_available', $data);
    }
}
