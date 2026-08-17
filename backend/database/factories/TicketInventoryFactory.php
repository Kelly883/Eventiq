<?php

namespace Database\Factories;

use App\Features\Inventory\Models\TicketInventory;
use App\Models\Event;
use App\Models\TicketTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketInventory>
 */
class TicketInventoryFactory extends Factory
{
    protected $model = TicketInventory::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_tier_id' => TicketTier::factory(),
            'total_allocated' => $this->faker->numberBetween(100, 1000),
            'total_sold' => $this->faker->numberBetween(0, 100),
            'low_stock_threshold' => 10,
            'last_updated_at' => now(),
        ];
    }
}
