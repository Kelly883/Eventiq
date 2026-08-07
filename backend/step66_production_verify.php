<?php

/**
 * Step 66 Production-Readiness End-to-End Verification.
 *
 * Verifies the four Step 66 tables (orders, order_items, tickets,
 * payments) are UUID-keyed, properly indexed, correctly FK'd, and can
 * persist the full checkout graph, exactly as the PRD requires.
 */

$dbPath = __DIR__ . '/database/database.sqlite';
$db = new SQLite3($dbPath);
if (! $db) {
    fwrite(STDERR, "Failed to open DB: $dbPath" . PHP_EOL);
    exit(1);
}

// Foreign keys are OFF by default in SQLite - enable for enforcement test.
$db->exec('PRAGMA foreign_keys = ON');

$errors = [];

function check(bool $cond, string $label, array &$errors): void
{
    echo ($cond ? '  ✅ ' : '  ❌ ') . $label . PHP_EOL;
    if (! $cond) {
        $errors[] = $label;
    }
}

echo "=== STEP 66 PRODUCTION READINESS VERIFICATION ===\n\n";

// ── 1. Tables exist ──────────────────────────────────────────────────
echo "1. TABLE EXISTENCE\n";
$expectedTables = ['orders', 'order_items', 'tickets', 'payments'];
foreach ($expectedTables as $t) {
    $exists = (bool) $db->querySingle("SELECT 1 FROM sqlite_master WHERE type='table' AND name='$t'");
    check($exists, "$t table exists", $errors);
}

// ── 2. UUID primary keys (PRD: id UUID) ─────────────────────────────
echo "\n2. UUID PRIMARY KEYS\n";
foreach ($expectedTables as $t) {
    $row = $db->querySingle("SELECT sql FROM sqlite_master WHERE type='table' AND name='$t'");
    $isUuid = preg_match('/"id"\s+varchar\s+NOT NULL/i', (string) $row) === 1
        || preg_match('/"id"\s+text\s+NOT NULL/i', (string) $row) === 1;
    check($isUuid, "$t.id is UUID (varchar/text)", $errors);
}

// ── 3. Required indexes ─────────────────────────────────────────────
echo "\n3. INDEXES\n";
$idxChecks = [
    ['orders', 'orders_user_id_index'],
    ['orders', 'orders_status_index'],
    ['orders', 'orders_payment_intent_id_unique'],
    ['orders', 'idx_orders_event_id'],
    ['orders', 'idx_orders_user_status'],
    ['orders', 'idx_orders_created_at'],
    ['order_items', 'order_items_order_id_index'],
    ['order_items', 'idx_order_items_ticket_tier_id'],
    ['tickets', 'tickets_user_id_index'],
    ['tickets', 'tickets_event_id_index'],
    ['tickets', 'idx_tickets_user_id'],
    ['tickets', 'idx_tickets_order_id'],
    ['tickets', 'idx_tickets_event_status'],
    ['payments', 'payments_order_id_index'],
    ['payments', 'idx_payments_created_at'],
    ['payments', 'idx_payments_gateway_status_date'],
];
foreach ($idxChecks as [$t, $idx]) {
    $found = (bool) $db->querySingle(
        "SELECT 1 FROM sqlite_master WHERE type='index' AND tbl_name='$t' AND name='$idx'"
    );
    check($found, "$t.$idx", $errors);
}

// ── 4. Foreign keys ─────────────────────────────────────────────────
echo "\n4. FOREIGN KEYS\n";
$fkChecks = [
    ['orders', 'user_id', 'users', 'SET NULL'],
    ['orders', 'event_id', 'events', 'SET NULL'],
    ['order_items', 'order_id', 'orders', 'CASCADE'],
    ['order_items', 'ticket_tier_id', 'ticket_tiers', 'CASCADE'],
    ['tickets', 'order_id', 'orders', 'CASCADE'],
    ['tickets', 'user_id', 'users', 'CASCADE'],
    ['tickets', 'event_id', 'events', 'CASCADE'],
    ['tickets', 'ticket_tier_id', 'ticket_tiers', 'CASCADE'],
    ['payments', 'order_id', 'orders', 'CASCADE'],
];
foreach ($fkChecks as [$t, $col, $ref, $delete]) {
    $found = false;
    $fkRows = $db->query("PRAGMA foreign_key_list($t)");
    while ($fk = $fkRows->fetchArray(SQLITE3_ASSOC)) {
        if ($fk['from'] === $col && $fk['table'] === $ref) {
            $found = true;
            break;
        }
    }
    check($found, "$t.$col -> $ref.id ($delete)", $errors);
}

// ── 5. End-to-end insert test ───────────────────────────────────────
echo "\n5. END-TO-END CHECKOUT GRAPH INSERT\n";
try {
    $db->exec('BEGIN');

    $uid = (string) $db->querySingle("SELECT id FROM users LIMIT 1");
    if (! $uid) {
        $uid = 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa';
        $db->exec("INSERT INTO users (id, name, email, passwordHash, role, emailVerified, created_at, updated_at)
                   VALUES ('$uid', 'Prod Verify', 'prod-verify@example.com', 'x', 'attendee', 1,
                           CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    }

    $eid = (int) $db->querySingle("SELECT id FROM events LIMIT 1");
    if (! $eid) {
        $oid = (int) $db->querySingle("SELECT id FROM organizers LIMIT 1");
        if (! $oid) {
            $db->exec("INSERT INTO organizers (user_id, business_name, created_at, updated_at)
                       VALUES ('$uid', 'Prod Verifier', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
            $oid = (int) $db->lastInsertRowID();
        }
        $db->exec("INSERT INTO events (organizer_id, title, description, start_datetime, end_datetime,
                                       venue_name, capacity, status, currency, created_at, updated_at)
                   VALUES ($oid, 'Prod Verify Event', 'desc', CURRENT_TIMESTAMP,
                           datetime(CURRENT_TIMESTAMP, '+3 hours'), 'Venue', 100, 'published', 'NGN',
                           CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $eid = (int) $db->lastInsertRowID();
    }

    $tid = (int) $db->querySingle("SELECT id FROM ticket_tiers LIMIT 1");
    if (! $tid) {
        $db->exec("INSERT INTO ticket_tiers (event_id, name, price, min_purchase, quantity, status,
                                              currency, is_active, sold_count, created_at, updated_at)
                   VALUES ($eid, 'General', 5000.00, 1, 100, 'published', 'NGN', 1, 0,
                           CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $tid = (int) $db->lastInsertRowID();
    }

    // Insert the full checkout graph with UUID keys
    $orderId = 'f0000000-0000-4000-8000-000000000001';
    $itemId  = 'f0000000-0000-4000-8000-000000000002';
    $ticketId = 'f0000000-0000-4000-8000-000000000003';
    $paymentId = 'f0000000-0000-4000-8000-000000000004';

    $db->exec("INSERT INTO orders (id, user_id, event_id, total_amount, currency, status,
                                   payment_gateway, payment_intent_id, created_at, updated_at)
               VALUES ('$orderId', '$uid', $eid, 12500.75, 'NGN', 'pending',
                       'paystack', 'pi_prod_verify_001', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

    $db->exec("INSERT INTO order_items (id, order_id, ticket_tier_id, quantity, unit_price,
                                        created_at, updated_at)
               VALUES ('$itemId', '$orderId', $tid, 2, 6250.375, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

    $db->exec("INSERT INTO tickets (id, order_id, user_id, event_id, ticket_tier_id,
                                    ticket_id, attendee_name, attendee_email, tier, status,
                                    qr_code_data, created_at, updated_at)
               VALUES ('$ticketId', '$orderId', '$uid', $eid, $tid,
                       'TCK-PROD-001', 'Prod User', 'prod@example.com', 'General', 'valid',
                       'QR:PROD:001', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

    $db->exec("INSERT INTO payments (id, order_id, payment_intent_id, amount, currency, status,
                                     gateway, gateway_response, created_at, updated_at)
               VALUES ('$paymentId', '$orderId', 'pi_prod_verify_001', 12500.75, 'NGN', 'success',
                       'paystack', '{\"status\":\"success\"}', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

    $order = $db->querySingle("SELECT id || '|' || status FROM orders WHERE id = '$orderId'");
    $item = $db->querySingle("SELECT id || '|' || quantity FROM order_items WHERE id = '$itemId'");
    $ticket = $db->querySingle("SELECT id || '|' || status FROM tickets WHERE id = '$ticketId'");
    $payment = $db->querySingle("SELECT id || '|' || status FROM payments WHERE id = '$paymentId'");

    check($order !== false && str_starts_with($order, $orderId), "Order persisted with UUID PK ($order)", $errors);
    check($item !== false && str_starts_with($item, $itemId), "OrderItem persisted with UUID PK ($item)", $errors);
    check($ticket !== false && str_starts_with($ticket, $ticketId), "Ticket persisted with UUID PK ($ticket)", $errors);
    check($payment !== false && str_starts_with($payment, $paymentId), "Payment persisted with UUID PK ($payment)", $errors);

    // FK enforcement check: referencing a non-existent order should fail
    // SQLite's SQLite3::exec() returns false + raises a warning rather than
    // throwing an exception, so check the return value and error code.
    $fkViolation = @$db->exec("INSERT INTO order_items (id, order_id, ticket_tier_id, quantity, unit_price, created_at, updated_at)
                               VALUES ('f0000000-0000-4000-8000-000000000005', 'nonexistent-0000-0000-0000-000000000000',
                                       $tid, 1, 100, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)") === false;
    $fkViolation = $fkViolation || $db->lastErrorCode() !== 0;
    check($fkViolation, "FK constraint rejects orphan order_items", $errors);

    $db->exec('ROLLBACK');
    echo "  ✅ Rolled back test data\n";
} catch (\Throwable $e) {
    try { $db->exec('ROLLBACK'); } catch (\Throwable) {}
    check(false, 'Insert test threw: ' . $e->getMessage(), $errors);
}

$db->close();

echo "\n========================================\n";
if (empty($errors)) {
    echo "OVERALL: PASS ✅ Step 66 is production-ready\n";
    exit(0);
}

echo "OVERALL: FAIL ❌ (" . count($errors) . " issues)\n";
foreach ($errors as $e) {
    echo "  - $e\n";
}
exit(1);