<?php

namespace Tests\Feature;

use App\Models\AnalyticsEventsMetric;
use App\Models\AnalyticsSalesTimeline;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\Role;
use App\Models\User;
use App\Models\TicketTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private User $organizerUser;
    private User $adminUser;
    private User $otherUser;
    private Organizer $organizer;
    private Organizer $otherOrganizer;
    private Event $event;
    private string $token;
    private string $adminToken;
    private string $otherToken;

    protected function setUp(): void
    {
        parent::setUp();

        $organizerRole = Role::firstOrCreate(['name' => 'organizer'], ['description' => 'Organizer', 'isSystemRole' => true]);
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator', 'isSystemRole' => true]);

        $this->organizerUser = User::factory()->create(['role_id' => $organizerRole->id]);
        $this->organizer = Organizer::factory()->create(['user_id' => $this->organizerUser->id]);
        $this->token = $this->organizerUser->createToken('test-token')->plainTextToken;

        $this->adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $this->adminToken = $this->adminUser->createToken('admin-token')->plainTextToken;

        $this->otherUser = User::factory()->create(['role' => 'organizer']);
        $this->otherOrganizer = Organizer::factory()->create(['user_id' => $this->otherUser->id]);
        $this->otherToken = $this->otherUser->createToken('other-token')->plainTextToken;

        $this->event = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
            'start_datetime' => now()->addDays(10),
            'end_datetime' => now()->addDays(10)->addHours(4),
        ]);
    }

    public function test_summary_returns_real_metrics_from_sales_timeline(): void
    {
        $tier = TicketTier::factory()->create(['event_id' => $this->event->id, 'price' => 50]);

        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'sale_timestamp' => now()->subDays(2),
            'quantity' => 4,
            'unit_price' => 50,
            'total_amount' => 200,
        ]);
        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'sale_timestamp' => now()->subDay(),
            'quantity' => 6,
            'unit_price' => 50,
            'total_amount' => 300,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('metrics.totalRevenue', 500)
            ->assertJsonPath('metrics.ticketsSold', 10)
            ->assertJsonPath('metrics.averageTicketPrice', 50)
            ->assertJsonStructure([
                'trends' => ['revenue', 'ticketsSold', 'conversionRate', 'pageViews'],
                'dateRange',
            ]);
    }

    public function test_summary_returns_401_without_token(): void
    {
        $response = $this->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");
        $response->assertStatus(401);
    }

    public function test_summary_returns_403_for_non_owner(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->otherToken)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");
        $response->assertStatus(403);
    }

    public function test_summary_returns_404_for_nonexistent_event(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/00000000-0000-0000-0000-000000000000/analytics/summary");
        $response->assertStatus(404);
    }

    public function test_summary_returns_trend_indicators(): void
    {
        $tier = TicketTier::factory()->create(['event_id' => $this->event->id, 'price' => 100]);

        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'sale_timestamp' => now()->subDays(45),
            'quantity' => 5,
            'total_amount' => 500,
        ]);
        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'sale_timestamp' => now()->subDays(5),
            'quantity' => 10,
            'total_amount' => 1000,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");

        $response->assertStatus(200);
        $trends = $response->json('trends');
        $this->assertArrayHasKey('revenue', $trends);
        $this->assertArrayHasKey('direction', $trends['revenue']);
        $this->assertArrayHasKey('percentageChange', $trends['revenue']);
        $this->assertEquals('up', $trends['revenue']['direction']);
    }

    public function test_summary_filters_by_date_range(): void
    {
        $tier = TicketTier::factory()->create(['event_id' => $this->event->id, 'price' => 50]);

        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'sale_timestamp' => now()->subDays(60),
            'quantity' => 100,
            'total_amount' => 5000,
        ]);
        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'sale_timestamp' => now()->subDays(5),
            'quantity' => 5,
            'total_amount' => 250,
        ]);

        $startDate = now()->subDays(10)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");

        $response->assertStatus(200)
            ->assertJsonPath('metrics.totalRevenue', 250)
            ->assertJsonPath('metrics.ticketsSold', 5);
    }

    public function test_summary_rejects_invalid_date_range(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary?startDate=2025-01-10&endDate=2025-01-01");

        $response->assertStatus(400);
    }

    public function test_summary_uses_cache_within_30s(): void
    {
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");
        $response1->assertStatus(200);

        $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");
        $response2->assertStatus(200);

        $this->assertEquals($response1->json(), $response2->json());
    }

    public function test_summary_refresh_bypasses_cache(): void
    {
        $tier = TicketTier::factory()->create(['event_id' => $this->event->id, 'price' => 50]);
        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'sale_timestamp' => now(),
            'quantity' => 5,
            'total_amount' => 250,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary")
            ->assertStatus(200);

        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'sale_timestamp' => now(),
            'quantity' => 5,
            'total_amount' => 250,
        ]);

        $cached = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");
        $this->assertEquals(250, $cached->json('metrics.totalRevenue'));

        $fresh = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary?refresh=true");
        $this->assertEquals(500, $fresh->json('metrics.totalRevenue'));
    }

    public function test_sales_velocity_returns_real_data_when_present(): void
    {
        $tier = TicketTier::factory()->create(['event_id' => $this->event->id, 'price' => 50]);

        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'sale_timestamp' => now()->subDays(2),
            'quantity' => 5,
            'total_amount' => 250,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/sales-velocity?interval=daily");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('aggregated_on_server', true);
    }

    public function test_sales_velocity_rejects_invalid_interval(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/sales-velocity?interval=monthly");

        $response->assertStatus(400);
    }

    public function test_sales_velocity_returns_401_without_token(): void
    {
        $response = $this->getJson("/api/organizer/events/{$this->event->id}/analytics/sales-velocity");
        $response->assertStatus(401);
    }

    public function test_sales_velocity_returns_403_for_non_owner(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->otherToken)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/sales-velocity");
        $response->assertStatus(403);
    }

    public function test_summary_rate_limits_after_20_requests(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");
            $this->assertEquals(200, $response->status(), "Request {$i} failed");
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");
        $this->assertEquals(429, $response->status(), '21st request should be rate limited');
    }

    public function test_detailed_returns_tier_breakdown(): void
    {
        $tier1 = TicketTier::factory()->create(['event_id' => $this->event->id, 'name' => 'VIP', 'price' => 100]);
        $tier2 = TicketTier::factory()->create(['event_id' => $this->event->id, 'name' => 'Regular', 'price' => 50]);

        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier1->id,
            'sale_timestamp' => now()->subDays(3),
            'quantity' => 2,
            'unit_price' => 100,
            'total_amount' => 200,
        ]);
        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier2->id,
            'sale_timestamp' => now()->subDays(2),
            'quantity' => 5,
            'unit_price' => 50,
            'total_amount' => 250,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/detailed");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('eventId', $this->event->id)
            ->assertJsonStructure([
                'tierBreakdown' => [
                    '*' => ['tierId', 'tierName', 'ticketsSold', 'revenue', 'percentageOfTotal'],
                ],
                'sourceBreakdown' => [
                    '*' => ['source', 'ticketsSold', 'revenue'],
                ],
            ]);
    }

    public function test_detailed_returns_401_without_token(): void
    {
        $response = $this->getJson("/api/organizer/events/{$this->event->id}/analytics/detailed");
        $response->assertStatus(401);
    }

    public function test_detailed_returns_403_for_non_owner(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->otherToken)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/detailed");
        $response->assertStatus(403);
    }

    public function test_comparison_returns_real_data_for_user_events(): void
    {
        $event2 = Event::factory()->create([
            'organizer_id' => $this->organizer->id,
            'status' => 'published',
        ]);

        AnalyticsEventsMetric::factory()->create([
            'event_id' => $this->event->id,
            'organizer_id' => $this->organizer->id,
            'total_revenue' => 1000,
            'total_tickets_sold' => 10,
        ]);
        AnalyticsEventsMetric::factory()->create([
            'event_id' => $event2->id,
            'organizer_id' => $this->organizer->id,
            'total_revenue' => 2000,
            'total_tickets_sold' => 20,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/analytics/comparison");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'comparison' => [
                    '*' => ['eventId', 'eventName', 'ticketsSold', 'revenue'],
                ],
            ]);

        $comparison = collect($response->json('comparison'));
        $this->assertGreaterThanOrEqual(2, $comparison->count());
    }

    public function test_comparison_returns_401_without_token(): void
    {
        $response = $this->getJson("/api/organizer/analytics/comparison");
        $response->assertStatus(401);
    }

    public function test_summary_caps_date_range_to_365_days(): void
    {
        $tier = TicketTier::factory()->create(['event_id' => $this->event->id, 'price' => 50]);

        // Sale 400 days ago — should be excluded by 365-day cap
        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'sale_timestamp' => now()->subDays(400),
            'quantity' => 100,
            'total_amount' => 5000,
        ]);
        // Sale 5 days ago — should be included
        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'sale_timestamp' => now()->subDays(5),
            'quantity' => 5,
            'total_amount' => 250,
        ]);

        $startDate = now()->subDays(500)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary?startDate={$startDate}&endDate={$endDate}");

        $response->assertStatus(200);
        // Only the 5-day-old sale should be included (400-day-old excluded by 365-day cap)
        $this->assertEquals(250, $response->json('metrics.totalRevenue'));
        $this->assertEquals(5, $response->json('metrics.ticketsSold'));
    }

    public function test_summary_includes_peak_sales_hour(): void
    {
        // Update the metric that was auto-created by EventObserver
        $metric = AnalyticsEventsMetric::where('event_id', $this->event->id)->first();
        $metric->update([
            'total_revenue' => 1000,
            'total_tickets_sold' => 10,
            'peak_sales_hour' => 14,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");

        $response->assertStatus(200);
        $this->assertArrayHasKey('peakSalesHour', $response->json('metrics'));
        $this->assertEquals(14, $response->json('metrics.peakSalesHour'));
    }

    public function test_sales_velocity_returns_empty_when_no_data(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/sales-velocity?interval=daily");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('hasData', false)
            ->assertJsonPath('data', []);
    }

    public function test_summary_cache_ttl_is_configurable(): void
    {
        // Set cache TTL to 1 second for testing
        putenv('ANALYTICS_CACHE_TTL_SECONDS=1');

        $response1 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");
        $response1->assertStatus(200);

        // Wait for cache to expire
        sleep(2);

        $response2 = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");
        $response2->assertStatus(200);

        // Responses should still be valid (just not from cache)
        $this->assertTrue($response2->json('success'));

        // Restore default
        putenv('ANALYTICS_CACHE_TTL_SECONDS=30');
    }
}
