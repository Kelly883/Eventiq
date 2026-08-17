<?php

namespace Tests\Unit;

use App\Models\AnalyticsEventsMetric;
use App\Models\Event;
use App\Models\Organizer;
use App\Models\TicketTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsEventsMetricTest extends TestCase
{
    use RefreshDatabase;

    private function makeMetric(array $overrides = []): AnalyticsEventsMetric
    {
        return AnalyticsEventsMetric::factory()->create($overrides);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function test_belongs_to_event(): void
    {
        $metric = $this->makeMetric();

        $this->assertInstanceOf(Event::class, $metric->event);
    }

    public function test_belongs_to_organizer(): void
    {
        $metric = $this->makeMetric();

        $this->assertInstanceOf(Organizer::class, $metric->organizer);
    }

    public function test_belongs_to_top_ticket_tier(): void
    {
        $tier = TicketTier::factory()->create();
        $metric = $this->makeMetric(['top_ticket_tier_id' => $tier->id]);

        $this->assertInstanceOf(TicketTier::class, $metric->topTicketTier);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_scope_for_event_filters_correctly(): void
    {
        $event = Event::factory()->create();

        $matching = AnalyticsEventsMetric::factory()->create(['event_id' => $event->id]);
        AnalyticsEventsMetric::factory()->create();

        $results = AnalyticsEventsMetric::forEvent($event->id)->get();

        $this->assertTrue($results->contains($matching));
        $this->assertCount(1, $results);
    }

    // -------------------------------------------------------------------------
    // Computed trend properties
    // -------------------------------------------------------------------------

    public function test_trend_attribute_defaults_to_flat(): void
    {
        $metric = $this->makeMetric();

        $this->assertSame('flat', $metric->trend);
    }

    public function test_revenue_trend_attribute_returns_direction(): void
    {
        $metric = $this->makeMetric();

        $this->assertSame('flat', $metric->revenue_trend);
    }

    public function test_tickets_sold_trend_attribute_returns_direction(): void
    {
        $metric = $this->makeMetric();

        $this->assertSame('flat', $metric->tickets_sold_trend);
    }

    public function test_conversion_rate_trend_attribute_returns_direction(): void
    {
        $metric = $this->makeMetric();

        $this->assertSame('flat', $metric->conversion_rate_trend);
    }
}
