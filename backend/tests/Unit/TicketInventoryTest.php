<?php

namespace Tests\Unit;

use App\Features\Inventory\Models\TicketInventory;
use App\Models\Event;
use App\Models\TicketTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeInventory(array $overrides = []): TicketInventory
    {
        return TicketInventory::factory()->create($overrides);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function test_total_available_computes_correctly(): void
    {
        $inventory = $this->makeInventory([
            'total_allocated' => 100,
            'total_sold' => 30,
        ]);

        $this->assertSame(70, $inventory->total_available);
    }

    public function test_total_available_returns_zero_when_sold_exceeds_allocated(): void
    {
        $inventory = new TicketInventory([
            'total_allocated' => 50,
            'total_sold' => 75,
        ]);

        $this->assertSame(0, $inventory->total_available);
    }

    public function test_total_available_returns_zero_when_allocated_is_zero(): void
    {
        $inventory = $this->makeInventory([
            'total_allocated' => 0,
            'total_sold' => 0,
        ]);

        $this->assertSame(0, $inventory->total_available);
    }

    public function test_is_low_stock_returns_true_when_within_threshold(): void
    {
        $inventory = $this->makeInventory([
            'total_allocated' => 100,
            'total_sold' => 92,
            'low_stock_threshold' => 10,
        ]);

        $this->assertTrue($inventory->is_low_stock);
    }

    public function test_is_low_stock_returns_false_when_above_threshold(): void
    {
        $inventory = $this->makeInventory([
            'total_allocated' => 100,
            'total_sold' => 5,
            'low_stock_threshold' => 10,
        ]);

        $this->assertFalse($inventory->is_low_stock);
    }

    public function test_is_low_stock_returns_false_when_sold_out(): void
    {
        $inventory = $this->makeInventory([
            'total_allocated' => 100,
            'total_sold' => 100,
            'low_stock_threshold' => 10,
        ]);

        $this->assertFalse($inventory->is_low_stock);
    }

    public function test_is_low_stock_returns_false_when_available_is_zero(): void
    {
        $inventory = $this->makeInventory([
            'total_allocated' => 100,
            'total_sold' => 100,
            'low_stock_threshold' => 0,
        ]);

        $this->assertFalse($inventory->is_low_stock);
    }

    // -------------------------------------------------------------------------
    // Default threshold
    // -------------------------------------------------------------------------

    public function test_default_low_stock_threshold_is_ten(): void
    {
        $inventory = new TicketInventory([
            'event_id' => Event::factory(),
            'ticket_tier_id' => TicketTier::factory(),
            'total_allocated' => 100,
            'total_sold' => 0,
        ]);

        $this->assertSame(10, $inventory->low_stock_threshold);
    }

    // -------------------------------------------------------------------------
    // Model validation
    // -------------------------------------------------------------------------

    public function test_creating_inventory_with_sold_exceeding_allocated_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TicketInventory::factory()->create([
            'total_allocated' => 10,
            'total_sold' => 20,
        ]);
    }

    public function test_updating_inventory_with_sold_exceeding_allocated_throws(): void
    {
        $inventory = $this->makeInventory([
            'total_allocated' => 100,
            'total_sold' => 20,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $inventory->update(['total_sold' => 150]);
    }
}
