<?php

namespace Database\Factories;

use App\Features\Checkout\Models\Ticket;
use App\Features\Checkout\Models\Order;
use App\Models\User;
use App\Models\Event;
use App\Models\TicketTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'event_id' => Event::factory(),
            'ticket_tier_id' => TicketTier::factory(),
            'ticket_id' => 'TKT-' . strtoupper($this->faker->unique()->bothify('??##?##')),
            'attendee_name' => $this->faker->name(),
            'attendee_email' => $this->faker->safeEmail(),
            'tier' => $this->faker->words(2, true),
            'status' => $this->faker->randomElement(['valid', 'checked_in', 'void', 'purged']),
            'qr_code_data' => $this->faker->uuid(),
            'qr_code_secret' => $this->faker->sha256(),
            'qr_code_generated_at' => now(),
            'qr_code_expires_at' => now()->addDays(30),
            'qr_code_scanned_count' => 0,
        ];
    }
}
