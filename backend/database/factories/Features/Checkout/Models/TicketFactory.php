<?php

namespace Database\Factories\Features\Checkout\Models;

use App\Features\Checkout\Models\Ticket;
use App\Features\Checkout\Models\Order;
use App\Models\Event;
use App\Models\TicketTier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
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
            'ticket_id' => 'TKT-' . strtoupper(uniqid()),
            'attendee_name' => $this->faker->name,
            'attendee_email' => $this->faker->unique()->safeEmail,
            'tier' => 'General',
            'status' => 'valid',
            'qr_code_data' => base64_encode($this->faker->sha256),
            'qr_code_secret' => password_hash($this->faker->sha256, PASSWORD_BCRYPT),
            'qr_code_generated_at' => now(),
            'qr_code_expires_at' => now()->addDays(7),
            'checked_in' => false,
            'checked_in_at' => null,
            'checked_in_by' => null,
            'qr_code_scanned_count' => 0,
            'last_qr_scan_at' => null,
            'first_scanned_at' => null,
        ];
    }
}
