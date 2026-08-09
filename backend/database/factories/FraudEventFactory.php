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
            'device_id' => $this->faker->uuid(),
            'card_country' => $this->faker->countryCode(),
            'device_fingerprint' => $this->faker->uuid(),
            'payment_method' => $this->faker->randomElement(['card', 'mobile_money', 'bank_transfer']),
            'payment_gateway' => $this->faker->randomElement(['paystack', 'flutterwave']),
            'user_orders_last_24h' => $this->faker->numberBetween(1, 10),
            'user_spend_last_24h' => $this->faker->randomFloat(2, 1000, 50000),
            'user_agent' => $this->faker->userAgent(),
            'referrer' => $this->faker->url(),
            'promo_code' => $this->faker->optional()->bothify('PROMO-###'),
            'is_archived' => false,
            'order_total' => $this->faker->randomFloat(2, 100, 10000),
            'ticket_quantity' => $this->faker->numberBetween(1, 5),
            'billing_country' => $this->faker->countryCode(),
            'billing_zip' => $this->faker->postcode(),
            'shipping_billing_match' => $this->faker->boolean(80),
            'user_email' => $this->faker->safeEmail(),
            'order_status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
            'device_type' => $this->faker->randomElement(['mobile', 'desktop', 'tablet', 'bot']),
            'proxy_vpn_detected' => $this->faker->boolean(10),
            'ip_reputation_score' => $this->faker->numberBetween(1, 100),
            'account_age_days' => $this->faker->numberBetween(1, 365),
            'payment_intent_id' => 'pi_' . $this->faker->uuid(),
            'chargeback_flag' => false,
            'authentication_method' => $this->faker->randomElement(['3DS', 'password', 'biometric', 'none']),
        ];
    }
}
