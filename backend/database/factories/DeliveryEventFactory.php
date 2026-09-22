<?php

namespace Database\Factories;

use App\Features\Delivery\Models\DeliveryEvent;
use App\Models\User;
use App\Models\Event;
use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeliveryEventFactory extends Factory
{
    protected $model = DeliveryEvent::class;

    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'user_id' => (string) \Illuminate\Support\Str::uuid(),
            'event_id' => Event::factory(),
            'order_id' => Order::factory(),
            'channel' => $this->faker->randomElement(['email', 'sms', 'dashboard']),
            'status' => $this->faker->randomElement(['pending', 'sent', 'delivered', 'failed']),
            'ticket_reference' => 'TKT-' . strtoupper($this->faker->bothify('??##?##')),
            'recipient' => $this->faker->safeEmail(),
            'subject' => 'Your ticket',
            'body' => 'Ticket delivery body',
            'sender' => config('ticket-delivery.email.from_address', 'noreply@eventiq.test'),
            'payload' => null,
            'provider' => $this->faker->randomElement(['smtp', 'termii', 'internal']),
            'provider_message_id' => null,
            'provider_response' => null,
            'error_message' => null,
            'attempt_count' => 0,
            'max_attempts' => 3,
            'last_attempt_at' => null,
            'delivered_at' => null,
            'next_retry_at' => null,
            'opened_at' => null,
            'clicked_at' => null,
            'archived_at' => null,
        ];
    }
}
