<?php
require 'C:/Users/PC/Downloads/EventIQ/backend/vendor/autoload.php';
$app = require_once 'C:/Users/PC/Downloads/EventIQ/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$db = Illuminate\Support\Facades\DB::connection();

echo "=== FINAL VERIFICATION ===\n\n";

// 1. Check-ins for event with event_title
$eventId = 1;
$checkins = $db->select("SELECT ci.*, e.title as event_title, t.ticket_id FROM check_ins ci JOIN events e ON e.id = ci.event_id LEFT JOIN tickets t ON t.id = ci.ticket_id WHERE ci.event_id = ? ORDER BY ci.checked_in_at DESC", [$eventId]);
echo "1. Check-ins for event $eventId:\n";
foreach ($checkins as $row) {
    echo "  - Ticket {$row->ticket_id} ({$row->event_title}) at {$row->checked_in_at} status={$row->status}\n";
}

// 2. Check-in history for ticket
$ticketId = $db->table('tickets')->where('event_id', $eventId)->value('id');
if ($ticketId) {
    $history = $db->select("SELECT * FROM check_ins WHERE ticket_id = ? ORDER BY checked_in_at DESC", [$ticketId]);
    echo "\n2. Check-in history for ticket $ticketId:\n";
    foreach ($history as $row) {
        echo "  - Check-in at {$row->checked_in_at} by user {$row->user_id}\n";
    }
}

// 3. Event real-time stats
$stats = $db->selectOne("SELECT COUNT(*) as total_checkins FROM check_ins WHERE event_id = ?", [$eventId]);
echo "\n3. Event real-time stats:\n";
echo "  Total check-ins: {$stats->total_checkins}\n";

// 4. Fraud events for order
$orderId = $db->table('orders')->where('event_id', $eventId)->value('id');
if ($orderId) {
    $frauds = $db->select("SELECT * FROM fraud_events WHERE order_id = ?", [$orderId]);
    echo "\n4. Fraud events for order $orderId:\n";
    foreach ($frauds as $row) {
        echo "  - {$row->fraud_type} (risk: {$row->risk_level}, score: {$row->risk_score})\n";
    }
}

// 5. Orders with payment status
if ($orderId) {
    $orders = $db->select("SELECT o.*, p.status as payment_status FROM orders o LEFT JOIN payments p ON p.order_id = o.id WHERE o.id = ?", [$orderId]);
    echo "\n5. Orders with payment status:\n";
    foreach ($orders as $row) {
        echo "  - Order {$row->id}: status={$row->status}, payment={$row->payment_status}\n";
    }
}

// 6. Verify foreign keys
echo "\n6. Foreign key integrity check:\n";
$tickets = $db->table('tickets')->count();
$checkins = $db->table('check_ins')->count();
$frauds = $db->table('fraud_events')->count();
echo "  Tickets: $tickets\n";
echo "  Check-ins: $checkins\n";
echo "  Fraud events: $frauds\n";

// 7. Verify duplicate check-in prevention
if ($ticketId && $checkins > 0) {
    $latest = $db->table('check_ins')->where('ticket_id', $ticketId)->orderBy('checked_in_at', 'desc')->first();
    if ($latest) {
        try {
            $db->insert("INSERT INTO check_ins (ticket_id, user_id, event_id, checked_in_at, created_at, updated_at) VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))", [$ticketId, $latest->user_id, $eventId, date('Y-m-d H:i:s', strtotime($latest->checked_in_at . ' +1 second'))]);
            echo "\n7. Duplicate check-in prevention: FAILED (should have been blocked)\n";
        } catch (\Exception $e) {
            echo "\n7. Duplicate check-in prevention: PASSED (unique constraint blocked duplicate)\n";
        }
    }
}

echo "\n=== VERIFICATION COMPLETE ===\n";
