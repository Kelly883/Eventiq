<?php

namespace Tests\Unit;

use App\Features\Inventory\Models\InventoryAdjustment;
use App\Models\Event;
use App\Models\TicketTier;
use App\Features\Pricing\Models\PricingWindow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdjustment(array $overrides = []): InventoryAdjustment
    {
        return InventoryAdjustment::factory()->create($overrides);
    }

    // -------------------------------------------------------------------------
    // Accessor
    // -------------------------------------------------------------------------

    public function test_quantity_delta_computes_correctly(): void
    {
        $adjustment = $this->makeAdjustment([
            'quantity_before' => 10,
            'quantity_after' => 25,
        ]);

        $this->assertSame(15, $adjustment->quantity_delta);
    }

    public function test_quantity_delta_handles_negative_change(): void
    {
        $adjustment = $this->makeAdjustment([
            'quantity_before' => 50,
            'quantity_after' => 35,
        ]);

        $this->assertSame(-15, $adjustment->quantity_delta);
    }

    public function test_quantity_delta_handles_no_change(): void
    {
        $adjustment = $this->makeAdjustment([
            'quantity_before' => 20,
            'quantity_after' => 20,
        ]);

        $this->assertSame(0, $adjustment->quantity_delta);
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function test_belongs_to_event(): void
    {
        $adjustment = $this->makeAdjustment();

        $this->assertInstanceOf(Event::class, $adjustment->event);
    }

    public function test_belongs_to_ticket_tier(): void
    {
        $adjustment = $this->makeAdjustment();

        $this->assertInstanceOf(TicketTier::class, $adjustment->ticketTier);
    }

    public function test_belongs_to_pricing_window(): void
    {
        $adjustment = $this->makeAdjustment();

        $this->assertInstanceOf(PricingWindow::class, $adjustment->pricingWindow);
    }

    public function test_belongs_to_organizer(): void
    {
        $adjustment = $this->makeAdjustment();

        $this->assertInstanceOf(User::class, $adjustment->organizer);
    }

    // -------------------------------------------------------------------------
    // Scope
    // -------------------------------------------------------------------------

    public function test_scope_for_event_filters_correctly(): void
    {
        $event = Event::factory()->create();

        $matching = InventoryAdjustment::factory()->create(['event_id' => $event->id]);
        InventoryAdjustment::factory()->create();

        $results = InventoryAdjustment::forEvent($event->id)->get();

        $this->assertTrue($results->contains($matching));
        $this->assertCount(1, $results);
    }

    // -------------------------------------------------------------------------
    // Immutability
    // -------------------------------------------------------------------------

    public function test_update_throws_exception(): void
    {
        $adjustment = $this->makeAdjustment();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Inventory adjustments are immutable and cannot be updated.');

        $adjustment->update(['reason' => 'new reason']);
    }

    public function test_delete_throws_exception(): void
    {
        $adjustment = $this->makeAdjustment();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Inventory adjustments are immutable and cannot be deleted.');

        $adjustment->delete();
    }
}
