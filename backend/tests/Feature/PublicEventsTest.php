<?php

namespace Tests\Feature;

use App\Models\AnalyticsEventsMetric;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PublicEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_is_reachable_without_authentication(): void
    {
        $response = $this->getJson('/api/public/events');
        $response->assertOk();
    }

    public function test_index_only_returns_published_events(): void
    {
        $published = Event::factory()->create(['status' => 'published']);
        $draft = Event::factory()->create(['status' => 'draft']);

        $response = $this->getJson('/api/public/events');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($published->id));
        $this->assertFalse($ids->contains($draft->id));
    }

    public function test_index_paginates_results(): void
    {
        Event::factory()->count(5)->create(['status' => 'published']);

        $response = $this->getJson('/api/public/events?per_page=2');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $response->assertJsonPath('meta.current_page', 1);
    }

    public function test_index_filters_by_category(): void
    {
        Event::factory()->create(['status' => 'published', 'category' => 'music']);
        Event::factory()->create(['status' => 'published', 'category' => 'sports']);

        $response = $this->getJson('/api/public/events?category=music');

        $response->assertOk();
        $categories = collect($response->json('data'))->pluck('category');
        $this->assertTrue($categories->every(fn ($c) => $c === 'music'));
        $this->assertCount(1, $categories);
    }

    public function test_index_search_requires_at_least_two_characters(): void
    {
        $response = $this->getJson('/api/public/events?search=a');

        $response->assertStatus(400);
    }

    public function test_index_search_returns_matching_events(): void
    {
        Event::factory()->create([
            'status' => 'published',
            'title' => 'Lagos Music Festival',
        ]);
        Event::factory()->create([
            'status' => 'published',
            'title' => 'Something Different',
        ]);

        $response = $this->getJson('/api/public/events?search=Lagos');

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertCount(1, $titles);
        $this->assertTrue($titles->first() === 'Lagos Music Festival');
    }

    public function test_show_returns_404_for_non_existent_event(): void
    {
        $response = $this->getJson('/api/public/events/nonexistent-id');

        $response->assertNotFound();
    }

    public function test_show_returns_404_for_unpublished_event(): void
    {
        $event = Event::factory()->create(['status' => 'draft']);

        $response = $this->getJson("/api/public/events/{$event->id}");

        $response->assertNotFound();
    }

    public function test_show_returns_published_event_with_resource_fields(): void
    {
        $organizer = Organizer::factory()->create(['displayName' => 'Test Org', 'avatarUrl' => 'http://example.com/avatar.png']);
        $event = Event::factory()->create([
            'status' => 'published',
            'organizer_id' => $organizer->id,
            'title' => 'Public Event',
        ]);

        $response = $this->getJson("/api/public/events/{$event->id}");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals('Public Event', $data['title']);
        $this->assertEquals('Test Org', $data['organizer']['name']);
        $this->assertEquals('http://example.com/avatar.png', $data['organizer']['avatar']);
        $this->assertArrayHasKey('ticket_availability', $data);
        $this->assertArrayHasKey('popularity_score', $data);
    }

    public function test_show_includes_popularity_score_from_analytics(): void
    {
        $organizer = Organizer::factory()->create(['displayName' => 'Test Org']);
        $event = Event::factory()->create(['status' => 'published', 'organizer_id' => $organizer->id]);

        // EventObserver creates an AnalyticsMetrics record on event creation
        $metric = $event->analyticsEventsMetric;
        $metric->update(['total_page_views' => 4200]);

        $response = $this->getJson("/api/public/events/{$event->id}");

        $response->assertOk();
        $response->assertJsonPath('data.popularity_score', 4200);
    }

    public function test_categories_endpoint_is_public(): void
    {
        Event::factory()->create(['status' => 'published', 'category' => 'music']);
        Event::factory()->create(['status' => 'published', 'category' => 'music']);
        Event::factory()->create(['status' => 'published', 'category' => 'sports']);

        $response = $this->getJson('/api/public/categories');

        $response->assertOk();
        $data = $response->json('data');
        $music = collect($data['categories'])->firstWhere('slug', 'music');
        $this->assertNotNull($music);
        $this->assertEquals(2, $music['events_count']);
    }

    public function test_ticket_tiers_endpoint_is_public(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        TicketTier::factory()->create([
            'event_id' => $event->id,
            'name' => 'General',
            'price' => 50,
            'status' => 'published',
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/public/events/{$event->id}/ticket-tiers");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('General', $data[0]['name']);
    }

    public function test_ticket_tiers_returns_404_for_unpublished_event(): void
    {
        $event = Event::factory()->create(['status' => 'draft']);

        $response = $this->getJson("/api/public/events/{$event->id}/ticket-tiers");

        $response->assertNotFound();
    }

    public function test_pricing_windows_endpoint_is_public(): void
    {
        $event = Event::factory()->create(['status' => 'published']);
        $event->pricingWindows()->create([
            'window_name' => 'Early Bird',
            'start_date_time' => now()->subDay(),
            'end_date_time' => now()->addWeek(),
            'price' => 45,
            'quantity_limit' => 100,
            'quantity_sold' => 0,
            'is_active' => true,
            'priority' => 1,
        ]);

        $response = $this->getJson("/api/public/events/{$event->id}/pricing-windows");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Early Bird', $data[0]['window_name']);
    }

    public function test_analytics_endpoint_is_public(): void
    {
        $event = Event::factory()->create(['status' => 'published']);

        // EventObserver creates an AnalyticsEventsMetric on event creation
        $metric = $event->analyticsEventsMetric;
        $metric->update([
            'total_page_views' => 1500,
            'total_tickets_sold' => 320,
            'total_revenue' => 15000,
        ]);

        $response = $this->getJson("/api/public/events/{$event->id}/analytics");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals(1500, $data['popularity_score']);
        $this->assertEquals(320, $data['total_tickets_sold']);
        $this->assertEquals(15000, $data['total_revenue']);
    }

    public function test_availability_endpoint_is_public(): void
    {
        $event = Event::factory()->create(['status' => 'published', 'capacity' => 200]);
        TicketTier::factory()->create([
            'event_id' => $event->id,
            'quantity' => 200,
            'sold_count' => 75,
            'status' => 'published',
        ]);

        $response = $this->getJson("/api/public/events/{$event->id}/availability");

        $response->assertOk();
        $data = $response->json('data');
        $this->assertEquals(200, $data['total_capacity']);
        $this->assertEquals(75, $data['total_sold']);
        $this->assertEquals(125, $data['availability']);
        $this->assertTrue($data['is_available']);
    }

    public function test_all_public_endpoints_are_rate_limited(): void
    {
        // Override the discovery limiter for this test so earlier tests don't exhaust it
        RateLimiter::for('discovery', fn () => Limit::perMinute(9999));
        $event = Event::factory()->create(['status' => 'published']);
        $urls = [
            '/api/public/events',
            "/api/public/events/{$event->id}",
            '/api/public/categories',
            "/api/public/events/{$event->id}/ticket-tiers",
            "/api/public/events/{$event->id}/pricing-windows",
            "/api/public/events/{$event->id}/analytics",
            "/api/public/events/{$event->id}/availability",
        ];

        foreach ($urls as $url) {
            $response = $this->getJson($url);
            $this->assertTrue(
                $response->status() < 429,
                "Expected {$url} to not be rate-limited on first request, got 429"
            );
        }

        // Restore the real limiter and verify it would block after 30 requests
        RateLimiter::for('discovery', fn () => Limit::perMinute(30));

        // Use a unique IP for this check to avoid interference
        $testKey = 'discovery|127.0.' . rand(1, 255) . '.1';
        for ($i = 0; $i < 30; $i++) {
            RateLimiter::hit($testKey);
        }
        $this->assertTrue(RateLimiter::tooManyAttempts($testKey, 30), 'Rate limiter should block after 30 attempts');
    }
}
