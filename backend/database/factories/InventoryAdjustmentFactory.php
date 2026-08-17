<?php

namespace Database\Factories;

use App\Features\Inventory\Models\InventoryAdjustment;
use App\Features\Pricing\Models\PricingWindow;
use App\Models\Event;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryAdjustment>
 */
class InventoryAdjustmentFactory extends Factory
{
    protected $model = InventoryAdjustment::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_tier_id' => TicketTier::factory(),
            'pricing_window_id' => PricingWindow::factory(),
            'organizer_id' => User::factory(),
            'adjustment_type' => $this->faker->randomElement(['manual_increase', 'manual_decrease', 'reallocation', 'system_correction']),
            'quantity_before' => $this->faker->numberBetween(0, 100),
            'quantity_after' => $this->faker->numberBetween(0, 100),
            'quantity_delta' => $this->faker->numberBetween(-50, 50),
            'reason' => $this->faker->sentence(),
        ];
    }
}
