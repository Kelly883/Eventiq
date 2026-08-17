<?php

namespace Tests\Unit;

use App\Models\AnalyticsSalesTimeline;
use App\Models\Event;
use App\Models\TicketTier;
use App\Features\Pricing\Models\PricingWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsSalesTimelineTest extends TestCase
{
    use RefreshDatabase;

    private function makeEntry(array $overrides = []): AnalyticsSalesTimeline
    {
        return AnalyticsSalesTimeline::factory()->create($overrides);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function test_belongs_to_event(): void
    {
        $entry = $this->makeEntry();

        $this->assertInstanceOf(Event::class, $entry->event);
    }

    public function test_belongs_to_ticket_tier(): void
    {
        $entry = $this->makeEntry();

        $this->assertInstanceOf(TicketTier::class, $entry->ticketTier);
    }

    public function test_belongs_to_pricing_window(): void
    {
        $entry = $this->makeEntry();

        $this->assertInstanceOf(PricingWindow::class, $entry->pricingWindow);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function test_scope_for_event_filters_correctly(): void
    {
        $event = Event::factory()->create();

        $matching = AnalyticsSalesTimeline::factory()->create(['event_id' => $event->id]);
        AnalyticsSalesTimeline::factory()->create();

        $results = AnalyticsSalesTimeline::forEvent($event->id)->get();

        $this->assertTrue($results->contains($matching));
        $this->assertCount(1, $results);
    }

    public function test_scope_by_date_range_filters_correctly(): void
    {
        $start = now()->subDays(10);
        $end = now()->subDays(5);

        $matching = AnalyticsSalesTimeline::factory()->create([
            'sale_timestamp' => now()->subDays(7),
        ]);
        AnalyticsSalesTimeline::factory()->create([
            'sale_timestamp' => now()->subDays(20),
        ]);

        $results = AnalyticsSalesTimeline::byDateRange($start, $end)->get();

        $this->assertTrue($results->contains($matching));
        $this->assertCount(1, $results);
    }

    public function test_scope_by_tier_filters_correctly(): void
    {
        $tier = TicketTier::factory()->create();

        $matching = AnalyticsSalesTimeline::factory()->create(['ticket_tier_id' => $tier->id]);
        AnalyticsSalesTimeline::factory()->create();

        $results = AnalyticsSalesTimeline::byTier($tier->id)->get();

        $this->assertTrue($results->contains($matching));
        $this->assertCount(1, $results);
    }

    // -------------------------------------------------------------------------
    // Immutability
    // -------------------------------------------------------------------------

    public function test_update_throws_exception(): void
    {
        $entry = $this->makeEntry();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Sales timeline entries are immutable and cannot be updated.');

        $entry->update(['quantity' => 999]);
    }

    public function test_delete_throws_exception(): void
    {
        $entry = $this->makeEntry();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Sales timeline entries are immutable and cannot be deleted.');

        $entry->delete();
    }
}
