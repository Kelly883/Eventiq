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
            'is_public' => true,
            'version' => 1,
        ];
    }

    /**
     * Ensure end_datetime is always after start_datetime.
     * afterMaking fires after attributes are merged but before save,
     * so the model's creating/updating hooks won't reject the record.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Event $event) {
            if ($event->end_datetime && $event->start_datetime) {
                $start = \Carbon\Carbon::parse($event->start_datetime);
                $end = \Carbon\Carbon::parse($event->end_datetime);
                if ($end->lessThanOrEqualTo($start)) {
                    $event->end_datetime = (clone $start)->addHours(3);
                }
            }
        });
    }
}
