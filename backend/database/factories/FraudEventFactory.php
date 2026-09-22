<?php

namespace Database\Factories;

use App\Features\Checkout\Models\Order;
use App\Models\User;
use App\Models\Event;
use App\Features\Fraud\Models\FraudEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class FraudEventFactory extends Factory
{
    protected $model = FraudEvent::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'ticket_id' => null,
            'event_id' => Event::factory(),
            'fraud_type' => $this->faker->randomElement([
                'duplicate_ticket_attempt',
                'velocity_check_failed',
                'payment_pattern_suspicious',
                'device_fingerprint_mismatch',
                'geolocation_anomaly',
                'card_testing',
                'high_risk_payment_method',
                'duplicate_checkin',
                'invalid_qr',
                'manual_override',
            ]),
            'risk_score' => $this->faker->randomFloat(2, 0, 100),
            'risk_level' => $this->faker->randomElement(['low', 'medium', 'high']),
            'detection_method' => $this->faker->randomElement([
                'sift_science',
                'stripe_radar',
                'duplicate_detection',
                'velocity_check',
                'rule_based',
                'qr_validation',
                'manual_review',
            ]),
            'fraud_factors' => [],
            'payment_details' => [],
            'velocity_metrics' => [],
            'device_info' => [],
            'duplicate_ticket_info' => [],
            'detected_at' => now(),
            'status' => $this->faker->randomElement(['flagged', 'reviewed', 'approved', 'rejected', 'auto_blocked']),
            'session_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->ipv4(),
            'card_fingerprint' => $this->faker->uuid(),
            'amount' => $this->faker->randomFloat(2, 100, 10000),
            'currency' => 'NGN',
            'payment_intent_id' => 'pi_' . $this->faker->uuid(),
            'chargeback_flag' => false,
            'authentication_method' => $this->faker->randomElement(['3DS', 'password', 'biometric', 'none']),
        ];
    }
}
