<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organizer;
use App\Models\Event;
use App\Models\TicketTier;
use App\Features\Checkout\Models\Order;
use App\Features\Checkout\Models\Ticket;
use App\Features\Checkout\Models\Payment;
use App\Features\CheckIn\Models\CheckIn;
use App\Features\Fraud\Models\FraudEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding database...');

        $organizer = User::factory()->create([
            'name' => 'Test Organizer',
            'email' => 'organizer_' . uniqid() . '@test.com',
            'passwordHash' => Hash::make('password'),
            'role' => 'organizer',
        ]);

        $organizerProfile = Organizer::factory()->create([
            'user_id' => $organizer->id,
            'displayName' => 'Test Organizer',
            'business_name' => 'Test Business',
        ]);

        $event = Event::factory()->create([
            'organizer_id' => $organizerProfile->id,
            'title' => 'Test Event',
            'status' => 'published',
            'capacity' => 100,
        ]);

        $tier = TicketTier::factory()->create([
            'event_id' => $event->id,
            'name' => 'General Admission',
            'price' => 5000,
            'quantity' => 100,
        ]);

        $attendee = User::factory()->create([
            'name' => 'Test Attendee',
            'email' => 'attendee_' . uniqid() . '@test.com',
            'passwordHash' => Hash::make('password'),
            'role' => 'attendee',
        ]);

        $order = Order::create([
            'user_id' => $attendee->id,
            'event_id' => $event->id,
            'status' => 'completed',
            'total_amount' => 5000,
            'payment_gateway' => 'paystack',
            'payment_intent_id' => 'pi_' . uniqid(),
        ]);

        $ticket = Ticket::create([
            'order_id' => $order->id,
            'user_id' => $attendee->id,
            'event_id' => $event->id,
            'ticket_tier_id' => $tier->id,
            'status' => 'valid',
            'ticket_id' => 'TKT-' . strtoupper(uniqid()),
            'attendee_name' => $attendee->name,
            'attendee_email' => $attendee->email,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'payment_intent_id' => $order->payment_intent_id,
            'gateway_transaction_id' => 'tx_' . uniqid(),
            'amount' => 5000,
            'currency' => 'NGN',
            'status' => 'success',
            'gateway' => 'paystack',
            'idempotency_key' => uniqid(),
            'gateway_response' => '{}',
            'fees' => 150,
            'net_amount' => 4850,
        ]);

        CheckIn::create([
            'ticket_id' => $ticket->id,
            'user_id' => $attendee->id,
            'event_id' => $event->id,
            'scanned_by' => $organizer->id,
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        FraudEvent::create([
            'order_id' => $order->id,
            'user_id' => $attendee->id,
            'ticket_id' => $ticket->id,
            'event_id' => $event->id,
            'fraud_type' => 'duplicate_checkin',
            'risk_score' => 85.5,
            'risk_level' => 'high',
            'detection_method' => 'qr_validation',
            'status' => 'flagged',
        ]);

        $this->command->info('Database seeded successfully!');
    }
}
