<?php

namespace Tests\Unit;

use App\Models\AnalyticsTierPerformance;
use App\Models\Event;
use App\Models\TicketTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTierPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private function makePerformance(array $overrides = []): AnalyticsTierPerformance
    {
        return AnalyticsTierPerformance::factory()->create($overrides);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function test_belongs_to_event(): void
    {
        $performance = $this->makePerformance();

        $this->assertInstanceOf(Event::class, $performance->event);
    }

    public function test_belongs_to_ticket_tier(): void
    {
        $performance = $this->makePerformance();

        $this->assertInstanceOf(TicketTier::class, $performance->ticketTier);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_scope_for_event_filters_correctly(): void
    {
        $event = Event::factory()->create();

        $matching = AnalyticsTierPerformance::factory()->create(['event_id' => $event->id]);
        AnalyticsTierPerformance::factory()->create();

        $results = AnalyticsTierPerformance::forEvent($event->id)->get();

        $this->assertTrue($results->contains($matching));
        $this->assertCount(1, $results);
    }

    public function test_scope_for_tier_filters_correctly(): void
    {
        $tier = TicketTier::factory()->create();

        $matching = AnalyticsTierPerformance::factory()->create(['ticket_tier_id' => $tier->id]);
        AnalyticsTierPerformance::factory()->create();

        $results = AnalyticsTierPerformance::forTier($tier->id)->get();

        $this->assertTrue($results->contains($matching));
        $this->assertCount(1, $results);
    }
}
