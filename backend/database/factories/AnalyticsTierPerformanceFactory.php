<?php

namespace Database\Factories;

use App\Models\AnalyticsTierPerformance;
use App\Models\Event;
use App\Models\TicketTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsTierPerformance>
 */
class AnalyticsTierPerformanceFactory extends Factory
{
    protected $model = AnalyticsTierPerformance::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'ticket_tier_id' => TicketTier::factory(),
            'total_sold' => $this->faker->numberBetween(0, 1000),
            'total_revenue' => $this->faker->randomFloat(2, 0, 50000),
            'average_price' => $this->faker->randomFloat(2, 10, 200),
            'percentage_of_total_sales' => $this->faker->randomFloat(2, 0, 100),
            'percentage_of_total_revenue' => $this->faker->randomFloat(2, 0, 100),
            'conversion_rate' => $this->faker->randomFloat(2, 0, 15),
            'last_updated_at' => now(),
        ];
    }
}
