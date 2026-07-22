<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'organizer_id' => Organizer::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'start_datetime' => now()->addDays(7),
            'end_datetime' => now()->addDays(7)->addHours(3),
            'venue_name' => $this->faker->company() . ' Venue',
            'venue_address' => $this->faker->address(),
            'status' => 'published',
            'capacity' => 100,
        ];
    }
}
