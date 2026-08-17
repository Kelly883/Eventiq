<?php

namespace Database\Factories;

use App\Features\Pricing\Models\PricingWindow;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PricingWindow>
 */
class PricingWindowFactory extends Factory
{
    protected $model = PricingWindow::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_category_id' => null,
            'window_name' => $this->faker->words(3, true),
            'start_date_time' => now()->subHours(1),
            'end_date_time' => now()->addHours(3),
            'price' => $this->faker->randomFloat(2, 10, 200),
            'quantity_limit' => $this->faker->numberBetween(50, 500),
            'quantity_sold' => 0,
            'is_active' => true,
            'priority' => $this->faker->numberBetween(0, 10),
        ];
    }
}
