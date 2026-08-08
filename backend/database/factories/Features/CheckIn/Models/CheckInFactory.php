<?php

namespace Database\Factories\Features\CheckIn\Models;

use App\Features\Checkout\Models\Ticket;
use App\Features\CheckIn\Models\CheckIn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckIn>
 */
class CheckInFactory extends Factory
{
    protected $model = CheckIn::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => null,
            'checked_in_at' => now(),
        ];
    }
}
