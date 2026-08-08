<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\TicketTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketTier>
 */
class TicketTierFactory extends Factory
{
    protected $model = TicketTier::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'name' => $this->faker->words(2, true),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'min_purchase' => 1,
            'quantity' => 100,
            'status' => 'published',
            'currency' => 'NGN',
            'is_active' => true,
            'sold_count' => 0,
        ];
    }
}
