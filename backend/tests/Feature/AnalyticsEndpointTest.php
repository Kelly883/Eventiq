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

        $organizerRole = Role::factory()->create(['name' => 'organizer']);
        $adminRole = Role::factory()->create(['name' => 'admin']);

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

    // -------------------------------------------------------------------------
    // SUMMARY: GET /api/organizer/events/{event}/analytics/summary
    // -------------------------------------------------------------------------

    public function test_summary_returns_real_metrics_from_analytics_table(): void
    {
        \App\Models\AnalyticsEventsMetric::create([
            'event_id' => $this->event->id,
            'organizer_id' => $this->organizer->id,
            'total_revenue' => 14520.00,
            'total_tickets_sold' => 324,
            'total_page_views' => 1760,
            'total_ticket_page_views' => 840,
            'conversion_rate' => 18.4,
            'average_ticket_price' => 44.81,
            'peak_sales_hour' => 19,
            'last_updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/summary");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('eventId', $this->event->id);

        $this->assertEquals(14520.00, $response->json('metrics.totalRevenue'));
        $this->assertEquals(324, $response->json('metrics.ticketsSold'));
        $this->assertEquals(1760, $response->json('metrics.pageViews'));
        $this->assertEquals(18.4, $response->json('metrics.conversionRate'));
    }

    public function test_summary_computes_metrics_from_sales_timeline_when_no_pre_aggregated_row(): void
    {
        // Pre-aggregate a row, then remove to force fallback path
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
            ->assertJsonPath('success', true);

        $this->assertEquals(500.0, $response->json('metrics.totalRevenue'));
        $this->assertEquals(10, $response->json('metrics.ticketsSold'));
        $this->assertEquals(50.0, $response->json('metrics.averageTicketPrice'));
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

    // -------------------------------------------------------------------------
    // DETAILED: GET /api/organizer/events/{event}/analytics/detailed
    // -------------------------------------------------------------------------

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

    public function test_detailed_aggregates_sources_correctly(): void
    {
        $tier = TicketTier::factory()->create(['event_id' => $this->event->id, 'price' => 50]);

        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'source' => 'web',
            'quantity' => 3,
            'total_amount' => 150,
        ]);
        AnalyticsSalesTimeline::factory()->create([
            'event_id' => $this->event->id,
            'ticket_tier_id' => $tier->id,
            'source' => 'mobile',
            'quantity' => 2,
            'total_amount' => 100,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/events/{$this->event->id}/analytics/detailed");

        $response->assertStatus(200);

        $sources = collect($response->json('sourceBreakdown'));
        $web = $sources->firstWhere('source', 'web');
        $mobile = $sources->firstWhere('source', 'mobile');

        $this->assertEquals(3, $web['ticketsSold']);
        $this->assertEquals(150.0, $web['revenue']);
        $this->assertEquals(2, $mobile['ticketsSold']);
        $this->assertEquals(100.0, $mobile['revenue']);
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

    // -------------------------------------------------------------------------
    // SALES VELOCITY: pre-existing — just sanity check still works with real data
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // COMPARISON: GET /api/organizer/analytics/comparison
    // -------------------------------------------------------------------------

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

    public function test_comparison_returns_only_own_events(): void
    {
        $otherEvent = Event::factory()->create([
            'organizer_id' => $this->otherOrganizer->id,
        ]);
        AnalyticsEventsMetric::factory()->create([
            'event_id' => $otherEvent->id,
            'organizer_id' => $this->otherOrganizer->id,
            'total_revenue' => 9999,
            'total_tickets_sold' => 999,
        ]);
        AnalyticsEventsMetric::factory()->create([
            'event_id' => $this->event->id,
            'organizer_id' => $this->organizer->id,
            'total_revenue' => 100,
            'total_tickets_sold' => 5,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/organizer/analytics/comparison");

        $response->assertStatus(200);

        $comparison = collect($response->json('comparison'));
        $this->assertLessThanOrEqual(1, $comparison->count());
        $this->assertNotContains(
            $otherEvent->id,
            $comparison->pluck('eventId')->toArray()
        );
    }
}
