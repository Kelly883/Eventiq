<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "=== Insert test fraud event record ===\n";

// Temporarily disable FK constraints for test data setup
DB::statement('PRAGMA foreign_keys = OFF');

// Ensure we have valid order and user records for FK constraints
$order = DB::table('orders')->first();
if (!$order) {
    $orderId = Str::uuid()->toString();
    DB::table('orders')->insert([
        'id' => $orderId,
        'total_amount' => 5000.00,
        'currency' => 'NGN',
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
} else {
    $orderId = $order->id;
}

$user = DB::table('users')->first();
if (!$user) {
    $userId = Str::uuid()->toString();
    DB::table('users')->insert([
        'id' => $userId,
        'name' => 'Test User',
        'email' => 'test@example.com',
        'passwordHash' => 'test',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);
} else {
    $userId = $user->id;
}

// Re-enable FK constraints
DB::statement('PRAGMA foreign_keys = ON');

$ticketId = DB::table('tickets')->first()->id ?? null;
$eventId = DB::table('events')->first()->id ?? null;

$record = [
    'id' => Str::uuid()->toString(),
    'order_id' => $orderId,
    'user_id' => $userId,
    'ticket_id' => $ticketId,
    'event_id' => $eventId,
    'fraud_type' => 'velocity_check_failed',
    'risk_score' => 85.50,
    'risk_level' => 'high',
    'detection_method' => 'duplicate_detection',
    'fraud_factors' => json_encode(['duplicate_qr_detected', 'multiple_orders']),
    'payment_details' => json_encode(['last4' => '4242', 'country' => 'NG']),
    'velocity_metrics' => json_encode(['orders_24h' => 5, 'total_spend' => 50000]),
    'device_info' => json_encode(['ip' => '127.0.0.1', 'user_agent' => 'Test']),
    'duplicate_ticket_info' => json_encode(['matching_tickets' => [$ticketId]]),
    'detected_at' => date('Y-m-d H:i:s'),
    'status' => 'flagged',
    'review_notes' => 'Automated detection',
    'session_id' => 'sess_123',
    'ip_address' => '127.0.0.1',
    'card_fingerprint' => 'fp_abc123',
    'amount' => 5000.00,
    'currency' => 'NGN',
    'gateway_response_code' => '00',
    'automated_action_taken' => 'flag',
    'source' => 'webhook',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
    
    // New denormalized columns
    'card_country' => 'NG',
    'device_fingerprint' => 'device_fp_123',
    'payment_method' => 'card',
    'payment_gateway' => 'paystack',
    'user_orders_last_24h' => 3,
    'user_spend_last_24h' => 25000.00,
    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'referrer' => 'https://eventiq.com/events/123',
    'promo_code' => 'EARLYBIRD',
    'escalated_to' => null,
    'escalated_at' => null,
    'resolution' => null,
    'evidence_snapshot' => json_encode(['order_total' => 5000, 'ticket_count' => 2]),
    'is_archived' => false,
    'archived_at' => null,
    // Additional analysis columns
    'order_total' => 5000.00,
    'ticket_quantity' => 2,
    'billing_country' => 'NG',
    'billing_zip' => '100001',
    'shipping_billing_match' => true,
];

DB::table('fraud_events')->insert($record);

echo "Record inserted successfully.\n";

echo "\n=== Verify inserted record ===\n";
$result = DB::table('fraud_events')->where('order_id', $orderId)->first();
if ($result) {
    echo "Found record: {$result->id}\n";
    echo "Fraud type: {$result->fraud_type}\n";
    echo "Risk level: {$result->risk_level}\n";
    echo "Status: {$result->status}\n";
    echo "Fraud factors: {$result->fraud_factors}\n";
    echo "Payment details: {$result->payment_details}\n";
    echo "Order total: {$result->order_total}\n";
    echo "Ticket quantity: {$result->ticket_quantity}\n";
    echo "Billing country: {$result->billing_country}\n";
    echo "Billing zip: {$result->billing_zip}\n";
    echo "Shipping/billing match: " . ($result->shipping_billing_match ? 'Yes' : 'No') . "\n";

echo "\nSUCCESS: Test record saves correctly.\n";
} else {
    echo "ERROR: Record not found.\n";
}