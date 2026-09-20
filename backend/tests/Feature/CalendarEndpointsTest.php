<?php

namespace Tests\Feature;

use App\Features\Checkout\Models\Ticket;
use App\Features\Inventory\Models\TicketInventory;
use App\Features\Pricing\Models\PricingWindow;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CalendarEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Override the discovery limiter to a high value so other tests
        // don't exhaust it. We'll restore and test it explicitly.
        RateLimiter::for('discovery', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(9999));
    }

    protected function tearDown(): void
    {
        // Restore the default discovery limiter
        RateLimiter::for('discovery', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by('127.0.0.1'));
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // GET /api/events/public/calendar — month overview
    // ------------------------------------------------------------------

    public function test_calendar_index_returns_published_events_only(): void
    {
        $published = Event::factory()->create(['status' => 'published']);
        Event::factory()->create(['status' => 'draft']);
        Event::factory()->create(['status' => 'cancelled']);

        $response = $this->getJson('/api/events/public/calendar');

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($published->id));
        $this->assertCount(1, $ids);
    }

    public function test_calendar_index_filters_by_month(): void
    {
        $inMonth = Event::factory()->create([
            'status' => 'published',
            'start_datetime' => now()->addDays(10),
        ]);
        $outOfMonth = Event::factory()->create([
            'status' => 'published',
            'start_datetime' => now()->addDays(400),
        ]);

        $response = $this->getJson('/api/events/public/calendar?start_date=' . now()->addDays(10)->toDateString() . '&end_date=' . now()->addDays(10)->toDateString());

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($inMonth->id));
        $this->assertFalse($ids->contains($outOfMonth->id));
    }

    public function test_calendar_index_filters_by_week(): void
    {
        $inWeek = Event::factory()->create([
            'status' => 'published',
            'start_datetime' => now()->addDays(2),
        ]);
        $outOfWeek = Event::factory()->create([
            'status' => 'published',
            'start_datetime' => now()->addDays(60),
        ]);

        $response = $this->getJson('/api/events/public/calendar?start_date=' . now()->toDateString() . '&end_date=' . now()->addWeek()->toDateString());

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($inWeek->id));
        $this->assertFalse($ids->contains($outOfWeek->id));
    }

    public function test_calendar_index_filters_by_category(): void
    {
        $music = Event::factory()->create(['status' => 'published', 'category' => 'music']);
        Event::factory()->create(['status' => 'published', 'category' => 'sports']);

        $response = $this->getJson('/api/events/public/calendar?category=music');

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($music->id));
        $this->assertCount(1, $ids);
    }

    public function test_calendar_index_filters_by_price_range(): void
    {
        $cheap = Event::factory()->create(['status' => 'published']);
        $expensive = Event::factory()->create(['status' => 'published']);

        PricingWindow::create([
            'event_id' => $cheap->id,
            'price' => 10.00,
            'is_active' => true,
            'window_name' => 'Cheap Window',
            'start_date_time' => now()->subHours(1),
            'end_date_time' => now()->addHours(3),
        ]);
        PricingWindow::create([
            'event_id' => $expensive->id,
            'price' => 500.00,
            'is_active' => true,
            'window_name' => 'Expensive Window',
            'start_date_time' => now()->subHours(1),
            'end_date_time' => now()->addHours(3),
        ]);

        $response = $this->getJson('/api/events/public/calendar?min_price=0&max_price=50');

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($cheap->id));
        $this->assertFalse($ids->contains($expensive->id));
    }

    public function test_calendar_index_filters_by_location(): void
    {
        $inLagos = Event::factory()->create([
            'status' => 'published',
            'venue_name' => 'Lagos Arena',
            'venue_address' => 'Lagos, Nigeria',
        ]);
        $inAbuja = Event::factory()->create([
            'status' => 'published',
            'venue_name' => 'Abuja Stadium',
            'venue_address' => 'Abuja, Nigeria',
        ]);

        $response = $this->getJson('/api/events/public/calendar?location=Lagos');

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($inLagos->id));
        $this->assertFalse($ids->contains($inAbuja->id));
    }

    public function test_calendar_index_returns_empty_when_no_events(): void
    {
        $response = $this->getJson('/api/events/public/calendar');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.events.data'));
    }

    // ------------------------------------------------------------------
    // GET /api/events/public/calendar/day/:date — day detail
    // ------------------------------------------------------------------

    public function test_calendar_day_detail_returns_events_for_specific_date(): void
    {
        $today = now()->toDateString();
        $event = Event::factory()->create([
            'status' => 'published',
            'start_datetime' => now(),
        ]);
        $other = Event::factory()->create([
            'status' => 'published',
            'start_datetime' => now()->addDays(5),
        ]);

        $response = $this->getJson('/api/events/public/calendar/day/' . $today);

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($event->id));
        $this->assertFalse($ids->contains($other->id));
    }

    public function test_calendar_day_detail_only_returns_published(): void
    {
        $today = now()->toDateString();
        $published = Event::factory()->create([
            'status' => 'published',
            'start_datetime' => now(),
        ]);
        Event::factory()->create([
            'status' => 'draft',
            'start_datetime' => now(),
        ]);

        $response = $this->getJson('/api/events/public/calendar/day/' . $today);

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($published->id));
        $this->assertCount(1, $ids);
    }

    public function test_calendar_day_detail_sorts_by_price(): void
    {
        $today = now()->toDateString();
        $cheap = Event::factory()->create(['status' => 'published', 'start_datetime' => now()]);
        $expensive = Event::factory()->create(['status' => 'published', 'start_datetime' => now()]);

        PricingWindow::factory()->create(['event_id' => $cheap->id, 'price' => 10.00, 'is_active' => true]);
        PricingWindow::factory()->create(['event_id' => $expensive->id, 'price' => 500.00, 'is_active' => true]);

        $response = $this->getJson('/api/events/public/calendar/day/' . $today . '?sort_by=price&sort=asc');

        $response->assertOk();
        $events = collect($response->json('data.events.data'));
        $this->assertTrue($events->first()['id'] === $cheap->id);
    }

    public function test_calendar_day_detail_sorts_by_popularity(): void
    {
        $today = now()->toDateString();
        $popular = Event::factory()->create(['status' => 'published', 'start_datetime' => now()]);
        $unpopular = Event::factory()->create(['status' => 'published', 'start_datetime' => now()]);

        // Create tickets for popular event
        Ticket::factory()->count(5)->create(['event_id' => $popular->id]);
        Ticket::factory()->count(1)->create(['event_id' => $unpopular->id]);

        $response = $this->getJson('/api/events/public/calendar/day/' . $today . '?sort_by=popularity&sort=desc');

        $response->assertOk();
        $events = collect($response->json('data.events.data'));
        $this->assertTrue($events->first()['id'] === $popular->id);
    }

    public function test_calendar_day_detail_returns_empty_for_no_events(): void
    {
        $response = $this->getJson('/api/events/public/calendar/day/2020-01-01');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.events.data'));
    }

    // ------------------------------------------------------------------
    // GET /api/events/public/calendar/range — date range
    // ------------------------------------------------------------------

    public function test_calendar_range_returns_events_between_dates(): void
    {
        $inRange = Event::factory()->create([
            'status' => 'published',
            'start_datetime' => now()->addDays(5),
        ]);
        $outOfRange = Event::factory()->create([
            'status' => 'published',
            'start_datetime' => now()->addDays(30),
        ]);

        $response = $this->getJson('/api/events/public/calendar/range?start_date=' . now()->toDateString() . '&end_date=' . now()->addWeek()->toDateString());

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($inRange->id));
        $this->assertFalse($ids->contains($outOfRange->id));
    }

    public function test_calendar_range_only_returns_published(): void
    {
        $published = Event::factory()->create([
            'status' => 'published',
            'start_datetime' => now()->addDays(5),
        ]);
        Event::factory()->create([
            'status' => 'draft',
            'start_datetime' => now()->addDays(5),
        ]);
        Event::factory()->create([
            'status' => 'cancelled',
            'start_datetime' => now()->addDays(5),
        ]);

        $response = $this->getJson('/api/events/public/calendar/range?start_date=' . now()->toDateString() . '&end_date=' . now()->addWeek()->toDateString());

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($published->id));
        $this->assertCount(1, $ids);
    }

    // ------------------------------------------------------------------
    // Validation — invalid dates and ranges
    // ------------------------------------------------------------------

    public function test_calendar_index_rejects_invalid_date_format(): void
    {
        $response = $this->getJson('/api/events/public/calendar?start_date=not-a-date');

        $response->assertStatus(422);
    }

    public function test_calendar_index_rejects_end_date_before_start_date(): void
    {
        $response = $this->getJson('/api/events/public/calendar?start_date=2026-09-20&end_date=2026-09-10');

        $response->assertStatus(422);
    }

    public function test_calendar_index_rejects_date_range_exceeding_365_days(): void
    {
        $response = $this->getJson('/api/events/public/calendar?start_date=2026-01-01&end_date=2027-01-02');

        $response->assertStatus(422);
    }

    public function test_calendar_day_detail_rejects_invalid_date_format(): void
    {
        $response = $this->getJson('/api/events/public/calendar/day/not-a-date');

        $response->assertStatus(400);
    }

    public function test_calendar_range_rejects_invalid_date_format(): void
    {
        $response = $this->getJson('/api/events/public/calendar/range?start_date=not-a-date&end_date=2026-09-20');

        $response->assertStatus(422);
    }

    public function test_calendar_range_rejects_end_date_before_start_date(): void
    {
        $response = $this->getJson('/api/events/public/calendar/range?start_date=2026-09-20&end_date=2026-09-10');

        $response->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // Security — status filter cannot be overridden
    // ------------------------------------------------------------------

    public function test_calendar_index_ignores_status_filter_and_forces_published(): void
    {
        $published = Event::factory()->create(['status' => 'published']);
        Event::factory()->create(['status' => 'draft']);
        Event::factory()->create(['status' => 'cancelled']);

        // Try to bypass by passing status=draft
        $response = $this->getJson('/api/events/public/calendar?status=draft');

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($published->id));
        $this->assertCount(1, $ids);
    }

    public function test_calendar_day_detail_ignores_status_filter(): void
    {
        $today = now()->toDateString();
        $published = Event::factory()->create(['status' => 'published', 'start_datetime' => now()]);
        Event::factory()->create(['status' => 'draft', 'start_datetime' => now()]);

        $response = $this->getJson('/api/events/public/calendar/day/' . $today . '?status=draft');

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($published->id));
        $this->assertCount(1, $ids);
    }

    public function test_calendar_range_ignores_status_filter(): void
    {
        $published = Event::factory()->create(['status' => 'published', 'start_datetime' => now()->addDays(5)]);
        Event::factory()->create(['status' => 'draft', 'start_datetime' => now()->addDays(5)]);

        $response = $this->getJson('/api/events/public/calendar/range?start_date=' . now()->toDateString() . '&end_date=' . now()->addWeek()->toDateString() . '&status=draft');

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($published->id));
        $this->assertCount(1, $ids);
    }

    // ------------------------------------------------------------------
    // Rate limiting
    // ------------------------------------------------------------------

    public function test_calendar_index_is_rate_limited(): void
    {
        // Restore the real limiter for this test
        RateLimiter::for('discovery', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by(request()->ip()));

        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/events/public/calendar')->assertOk();
        }

        $this->getJson('/api/events/public/calendar')->assertStatus(429);
    }

    public function test_calendar_day_detail_is_rate_limited(): void
    {
        RateLimiter::for('discovery', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by(request()->ip()));

        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/events/public/calendar/day/' . now()->toDateString())->assertOk();
        }

        $this->getJson('/api/events/public/calendar/day/' . now()->toDateString())->assertStatus(429);
    }

    public function test_calendar_range_is_rate_limited(): void
    {
        RateLimiter::for('discovery', fn () => \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by(request()->ip()));

        for ($i = 0; $i < 30; $i++) {
            $this->getJson('/api/events/public/calendar/range?start_date=' . now()->toDateString() . '&end_date=' . now()->addWeek()->toDateString())->assertOk();
        }

        $this->getJson('/api/events/public/calendar/range?start_date=' . now()->toDateString() . '&end_date=' . now()->addWeek()->toDateString())->assertStatus(429);
    }

    // ------------------------------------------------------------------
    // Response structure
    // ------------------------------------------------------------------

    public function test_calendar_index_response_structure(): void
    {
        Event::factory()->create(['status' => 'published']);

        $response = $this->getJson('/api/events/public/calendar');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'events' => [
                    'data' => [
                        '*' => [
                            'id',
                            'organizer_id',
                            'title',
                            'start_datetime',
                            'end_datetime',
                            'venue_name',
                            'venue_address',
                            'status',
                            'category',
                            'capacity',
                            'total_available',
                            'total_sold',
                            'min_price',
                            'max_price',
                            'popularity',
                        ],
                    ],
                ],
                'summary',
                'filters' => [
                    'start_date',
                    'end_date',
                    'status',
                    'category',
                    'location',
                    'min_price',
                    'max_price',
                    'organizer_id',
                    'per_page',
                    'sort_by',
                    'sort',
                ],
            ],
        ]);
    }

    public function test_calendar_day_detail_response_structure(): void
    {
        Event::factory()->create(['status' => 'published', 'start_datetime' => now()]);

        $response = $this->getJson('/api/events/public/calendar/day/' . now()->toDateString());

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'events' => [
                    'data' => [
                        '*' => [
                            'id',
                            'organizer_id',
                            'title',
                            'start_datetime',
                            'end_datetime',
                            'venue_name',
                            'venue_address',
                            'status',
                            'category',
                            'capacity',
                            'total_available',
                            'total_sold',
                            'min_price',
                            'max_price',
                            'popularity',
                        ],
                    ],
                ],
                'summary',
                'filters',
            ],
        ]);
    }

    public function test_calendar_range_response_structure(): void
    {
        Event::factory()->create(['status' => 'published', 'start_datetime' => now()->addDays(5)]);

        $response = $this->getJson('/api/events/public/calendar/range?start_date=' . now()->toDateString() . '&end_date=' . now()->addWeek()->toDateString());

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'events' => [
                    'data' => [
                        '*' => [
                            'id',
                            'organizer_id',
                            'title',
                            'start_datetime',
                            'end_datetime',
                            'venue_name',
                            'venue_address',
                            'status',
                            'category',
                            'capacity',
                            'total_available',
                            'total_sold',
                            'min_price',
                            'max_price',
                            'popularity',
                        ],
                    ],
                ],
                'summary',
                'filters',
            ],
        ]);
    }

    // ------------------------------------------------------------------
    // Edge cases
    // ------------------------------------------------------------------

    public function test_calendar_index_with_extreme_price_range(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        PricingWindow::create([
            'event_id' => $event->id,
            'price' => 10.00,
            'is_active' => true,
            'window_name' => 'Test Window',
            'start_date_time' => now()->subHours(1),
            'end_date_time' => now()->addHours(3),
        ]);

        // min_price > max_price should return 422 (validation fails)
        $response = $this->getJson('/api/events/public/calendar?min_price=100&max_price=10');

        $response->assertStatus(422);
    }

    public function test_calendar_index_with_negative_price(): void
    {
        $response = $this->getJson('/api/events/public/calendar?min_price=-10');

        $response->assertStatus(422);
    }

    public function test_calendar_index_with_zero_per_page(): void
    {
        $response = $this->getJson('/api/events/public/calendar?per_page=0');

        $response->assertStatus(422);
    }

    public function test_calendar_index_caps_per_page_at_200(): void
    {
        Event::factory()->count(5)->create(['status' => 'published']);

        $response = $this->getJson('/api/events/public/calendar?per_page=200');

        $response->assertOk();
        $this->assertLessThanOrEqual(200, count($response->json('data.events.data')));
    }

    public function test_calendar_index_with_invalid_sort_by(): void
    {
        $response = $this->getJson('/api/events/public/calendar?sort_by=invalid');

        $response->assertStatus(422);
    }

    public function test_calendar_index_with_invalid_sort_direction(): void
    {
        $response = $this->getJson('/api/events/public/calendar?sort=invalid');

        $response->assertStatus(422);
    }

    public function test_calendar_index_with_invalid_category(): void
    {
        $response = $this->getJson('/api/events/public/calendar?category=' . str_repeat('a', 300));

        $response->assertStatus(422);
    }

    public function test_calendar_index_with_invalid_organizer_id(): void
    {
        $response = $this->getJson('/api/events/public/calendar?organizer_id=0');

        $response->assertStatus(422);
    }

    public function test_calendar_index_with_negative_organizer_id(): void
    {
        $response = $this->getJson('/api/events/public/calendar?organizer_id=-1');

        $response->assertStatus(422);
    }

    public function test_calendar_index_with_invalid_page(): void
    {
        $response = $this->getJson('/api/events/public/calendar?page=0');

        $response->assertStatus(422);
    }

    public function test_calendar_index_with_negative_page(): void
    {
        $response = $this->getJson('/api/events/public/calendar?page=-1');

        $response->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // Combined filters
    // ------------------------------------------------------------------

    public function test_calendar_index_with_combined_filters(): void
    {
        $match = Event::factory()->create([
            'status' => 'published',
            'category' => 'music',
            'venue_name' => 'Lagos Arena',
            'start_datetime' => now()->addDays(5),
        ]);
        Event::factory()->create([
            'status' => 'published',
            'category' => 'sports',
            'venue_name' => 'Lagos Arena',
            'start_datetime' => now()->addDays(5),
        ]);
        Event::factory()->create([
            'status' => 'published',
            'category' => 'music',
            'venue_name' => 'Abuja Stadium',
            'start_datetime' => now()->addDays(5),
        ]);

        PricingWindow::factory()->create(['event_id' => $match->id, 'price' => 50.00, 'is_active' => true]);

        $response = $this->getJson('/api/events/public/calendar?category=music&location=Lagos&start_date=' . now()->toDateString() . '&end_date=' . now()->addWeek()->toDateString());

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($match->id));
        $this->assertCount(1, $ids);
    }

    // ------------------------------------------------------------------
    // Availability status
    // ------------------------------------------------------------------

    public function test_calendar_index_returns_availability_status(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        TicketInventory::factory()->create([
            'event_id' => $event->id,
            'total_allocated' => 100,
            'total_sold' => 50,
        ]);

        $response = $this->getJson('/api/events/public/calendar');

        $response->assertOk();
        $events = collect($response->json('data.events.data'));
        $eventData = $events->firstWhere('id', $event->id);
        $this->assertNotNull($eventData);
        $this->assertEquals(50, $eventData['total_available']);
        $this->assertEquals(50, $eventData['total_sold']);
    }

    public function test_calendar_index_returns_sold_out_status(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        TicketInventory::factory()->create([
            'event_id' => $event->id,
            'total_allocated' => 100,
            'total_sold' => 100,
        ]);

        $response = $this->getJson('/api/events/public/calendar');

        $response->assertOk();
        $events = collect($response->json('data.events.data'));
        $eventData = $events->firstWhere('id', $event->id);
        $this->assertNotNull($eventData);
        $this->assertEquals(0, $eventData['total_available']);
    }

    public function test_calendar_index_returns_no_inventory_status(): void
    {
        $event = Event::factory()->create(['status' => 'published']);

        $response = $this->getJson('/api/events/public/calendar');

        $response->assertOk();
        $events = collect($response->json('data.events.data'));
        $eventData = $events->firstWhere('id', $event->id);
        $this->assertNotNull($eventData);
        $this->assertEquals(0, $eventData['total_available']);
        $this->assertEquals(0, $eventData['total_sold']);
    }

    // ------------------------------------------------------------------
    // is_public filter
    // ------------------------------------------------------------------

    public function test_calendar_index_excludes_private_events(): void
    {
        $public = Event::factory()->create(['status' => 'published', 'is_public' => true]);
        Event::factory()->create(['status' => 'published', 'is_public' => false]);

        $response = $this->getJson('/api/events/public/calendar');

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($public->id));
        $this->assertCount(1, $ids);
    }

    public function test_calendar_day_detail_excludes_private_events(): void
    {
        $today = now()->toDateString();
        $public = Event::factory()->create(['status' => 'published', 'is_public' => true, 'start_datetime' => now()]);
        Event::factory()->create(['status' => 'published', 'is_public' => false, 'start_datetime' => now()]);

        $response = $this->getJson('/api/events/public/calendar/day/' . $today);

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($public->id));
        $this->assertCount(1, $ids);
    }

    public function test_calendar_range_excludes_private_events(): void
    {
        $public = Event::factory()->create(['status' => 'published', 'is_public' => true, 'start_datetime' => now()->addDays(5)]);
        Event::factory()->create(['status' => 'published', 'is_public' => false, 'start_datetime' => now()->addDays(5)]);

        $response = $this->getJson('/api/events/public/calendar/range?start_date=' . now()->toDateString() . '&end_date=' . now()->addWeek()->toDateString());

        $response->assertOk();
        $ids = collect($response->json('data.events.data'))->pluck('id');
        $this->assertTrue($ids->contains($public->id));
        $this->assertCount(1, $ids);
    }

    // ------------------------------------------------------------------
    // Caching
    // ------------------------------------------------------------------

    public function test_calendar_index_caches_summary(): void
    {
        $event = Event::factory()->create(['status' => 'published', 'is_public' => true]);
        \App\Models\EventsCalendarSummary::refreshForDate(now()->toDateString());

        // First request — should hit the database
        $response1 = $this->getJson('/api/events/public/calendar');
        $response1->assertOk();

        // Create another event — should NOT appear in cached summary
        Event::factory()->create(['status' => 'published', 'is_public' => true]);

        // Second request — should use cached summary
        $response2 = $this->getJson('/api/events/public/calendar');
        $response2->assertOk();

        // The summary should be cached (only 1 event in summary)
        $this->assertCount(1, $response2->json('data.summary'));
    }

    public function test_calendar_index_cache_is_used_for_subsequent_requests(): void
    {
        $event = Event::factory()->create(['status' => 'published', 'is_public' => true]);
        \App\Models\EventsCalendarSummary::refreshForDate(now()->toDateString());

        // First request — populates cache
        $response1 = $this->getJson('/api/events/public/calendar');
        $response1->assertOk();
        $this->assertCount(1, $response1->json('data.summary'));

        // Create another event and refresh summary — cache should still return old data
        Event::factory()->create(['status' => 'published', 'is_public' => true]);
        \App\Models\EventsCalendarSummary::refreshForDate(now()->toDateString());

        // Second request — should use cached summary (still 1 event)
        $response2 = $this->getJson('/api/events/public/calendar');
        $response2->assertOk();
        $this->assertCount(1, $response2->json('data.summary'));
    }

    // ------------------------------------------------------------------
    // Eager loading (N+1 prevention)
    // ------------------------------------------------------------------

    public function test_calendar_index_eager_loads_organizer(): void
    {
        $organizer = Organizer::factory()->create(['displayName' => 'Test Organizer']);
        Event::factory()->create(['status' => 'published', 'is_public' => true, 'organizer_id' => $organizer->id]);

        $response = $this->getJson('/api/events/public/calendar');

        $response->assertOk();
        $events = collect($response->json('data.events.data'));
        $eventData = $events->first();
        $this->assertNotNull($eventData);
        // Organizer should be loaded (not null)
        $this->assertArrayHasKey('organizer', $eventData);
    }
}
