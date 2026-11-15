<?php

namespace Database\Factories;

use App\Features\CheckIn\Models\CheckIn;
use App\Features\Checkout\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckInFactory extends Factory
{
    protected $model = CheckIn::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'event_id' => 1,
            'scanned_by' => User::factory(),
            'status' => $this->faker->randomElement(['checked_in', 'failed', 'duplicate', 'expired']),
            'device_type' => $this->faker->randomElement(['mobile', 'kiosk', 'handheld']),
            'device_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'qr_verified' => $this->faker->boolean(90),
            'failure_reason' => $this->faker->boolean(10) ? $this->faker->sentence() : null,
            'checked_in_at' => now(),
            'client_mutation_id' => $this->faker->uuid(),
            'ticket_tier_id' => 1,
            'attendee_name' => $this->faker->name(),
            'attendee_email' => $this->faker->safeEmail(),
            'gate_number' => $this->faker->randomElement(['A', 'B', 'C', 'D']) . $this->faker->numberBetween(1, 10),
            'scanned_by_name' => $this->faker->name(),
        ];
    }
}
