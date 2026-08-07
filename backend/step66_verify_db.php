<?php

$dbPath = __DIR__ . '/database/database.sqlite';
echo "Opening: $dbPath (exists=" . (file_exists($dbPath) ? 'YES' : 'NO') . ', size=' . (file_exists($dbPath) ? filesize($dbPath) : 0) . " bytes)" . PHP_EOL . PHP_EOL;

$db = new SQLite3($dbPath);
if (!$db) { echo "Failed to open" . PHP_EOL; exit(1); }

$expectedTables = ['orders','order_items','tickets','payments'];
$allPass = true;

echo "=== TABLE & COLUMN CHECK ===" . PHP_EOL;
$expectedCols = [
    'orders' => ['id','user_id','event_id','total_amount','currency','status','payment_gateway','payment_intent_id','created_at','updated_at'],
    'order_items' => ['id','order_id','ticket_tier_id','quantity','unit_price','created_at','updated_at'],
    'tickets' => ['id','order_id','user_id','event_id','ticket_tier_id','qr_code_data','status','checked_in_at','created_at','updated_at'],
    'payments' => ['id','order_id','payment_intent_id','amount','currency','status','gateway','gateway_response','created_at','updated_at'],
];
foreach ($expectedTables as $tbl) {
    $res = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$tbl}'");
    $found = $res && ($row = $res->fetchArray(SQLITE3_ASSOC));
    if (!$found) {
        echo "[$tbl] MISSING TABLE ❌" . PHP_EOL;
        $allPass = false;
        continue;
    }
    $colRows = $db->query("PRAGMA table_info({$tbl})");
    $actualCols = [];
    while ($row = $colRows->fetchArray(SQLITE3_ASSOC)) { $actualCols[] = $row['name']; }
    $missing = array_diff($expectedCols[$tbl], $actualCols);
    $pass = empty($missing);
    $allPass = $allPass && $pass;
    echo "[$tbl] " . ($pass ? "PASS ✅" : "FAIL ❌ missing=" . implode(',', $missing)) . " (actual " . count($actualCols) . " cols: " . implode(',', $actualCols) . ")" . PHP_EOL;
}

echo PHP_EOL . "=== INDEX CHECK ===" . PHP_EOL;
$expectedIdx = [
    'orders' => ['orders_user_id_index','orders_status_index','orders_payment_intent_id_unique'],
    'order_items' => ['order_items_order_id_index'],
    'tickets' => ['tickets_user_id_index','tickets_event_id_index'],
    'payments' => ['payments_order_id_index'],
];
foreach ($expectedIdx as $tbl => $needles) {
    $rows = $db->query("PRAGMA index_list({$tbl})");
    $actualIdx = [];
    while ($rows && ($r = $rows->fetchArray(SQLITE3_ASSOC))) {
        $actualIdx[] = ['name'=>$r['name'],'unique'=>$r['unique']];
    }
    $actualNames = array_map('strtolower', array_column($actualIdx, 'name'));
    foreach ($needles as $needle) {
        $found = in_array(strtolower($needle), $actualNames, true);
        $allPass = $allPass && $found;
        $meta = null;
        foreach ($actualIdx as $a) { if (strtolower($a['name']) === strtolower($needle)) { $meta = $a; break; } }
        echo "  [$tbl] $needle : " . ($found ? "OK" . ($meta && $meta['unique'] ? " (UNIQUE)" : "") : "MISSING ❌") . PHP_EOL;
    }
}

echo PHP_EOL . "=== FOREIGN KEY CHECK ===" . PHP_EOL;
$expectedFK = [
    'orders' => [['from'=>'user_id','table'=>'users'],['from'=>'event_id','table'=>'events']],
    'order_items' => [['from'=>'order_id','table'=>'orders'],['from'=>'ticket_tier_id','table'=>'ticket_tiers']],
    'tickets' => [['from'=>'order_id','table'=>'orders'],['from'=>'user_id','table'=>'users'],['from'=>'event_id','table'=>'events'],['from'=>'ticket_tier_id','table'=>'ticket_tiers']],
    'payments' => [['from'=>'order_id','table'=>'orders']],
];
foreach ($expectedFK as $tbl => $rules) {
    $rows = $db->query("PRAGMA foreign_key_list({$tbl})");
    $actual = [];
    while ($rows && ($r = $rows->fetchArray(SQLITE3_ASSOC))) { $actual[] = $r; }
    foreach ($rules as $rule) {
        $found = null;
        foreach ($actual as $a) {
            if ($a['from'] === $rule['from'] && $a['table'] === $rule['table']) { $found = $a; break; }
        }
        $allPass = $allPass && ($found !== null);
        echo "  [$tbl] {$rule['from']} -> {$rule['table']}.id : " . ($found ? "OK (ON DELETE {$found['on_delete']})" : "MISSING ❌") . PHP_EOL;
    }
}

echo PHP_EOL . "=== SAMPLE INSERT + QUERY TEST ===" . PHP_EOL;
try {
    $db->exec('BEGIN');

    $uid = $db->querySingle("SELECT id FROM users LIMIT 1");
    $eid = $db->querySingle("SELECT id FROM events LIMIT 1");
    $tid = $db->querySingle("SELECT id FROM ticket_tiers LIMIT 1");

    if (!$uid || !$eid || !$tid) {
        echo "  NOTE: No seed data in users/events/ticket_tiers (u=$uid e=$eid t=$tid). Skipping real FK insert test." . PHP_EOL;
        echo "  Creating temporary test user/event/ticket_tier for FK integrity validation..." . PHP_EOL;

        $stmt = $db->prepare("INSERT INTO users (id, email, first_name, last_name, created_at, updated_at) VALUES (:id, 'step66-test@x.y', 'A', 'B', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $tmpUid = 'a0000000-0000-0000-0000-000000000001';
        $stmt->bindValue(':id', $tmpUid); $stmt->execute();

        $stmt = $db->prepare("INSERT INTO events (id, organizer_id, title, slug, description, status, event_date, start_time, end_time, venue_address, created_at, updated_at) VALUES (99999999, 1, 'Step66 Test', 'step66-test', 'x', 'published', '2024-03-15', '19:00', '22:00', 'x', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $tmpEid = 99999999;
        $stmt->execute();

        $stmt = $db->prepare("INSERT INTO ticket_tiers (id, event_id, name, price, capacity, created_at, updated_at) VALUES (99999999, 99999999, 'General', 5000.00, 100, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
        $tmpTid = 99999999;
        $stmt->execute();
        $uid = $tmpUid; $eid = $tmpEid; $tid = $tmpTid;
    }

    $oid = 'f0000000-0000-0000-0000-000000000001';
    $db->exec("INSERT INTO orders (id, user_id, event_id, total_amount, currency, status, payment_gateway, payment_intent_id, created_at, updated_at) VALUES ('$oid', '$uid', $eid, 12500.75, 'NGN', 'pending', 'paystack', 'pi-step66-xyz', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

    $oiid = 'f1000000-0000-0000-0000-000000000001';
    $db->exec("INSERT INTO order_items (id, order_id, ticket_tier_id, quantity, unit_price, created_at, updated_at) VALUES ('$oiid', '$oid', $tid, 5, 2500.15, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

    $tkid = 'f2000000-0000-0000-0000-000000000001';
    $db->exec("INSERT INTO tickets (id, order_id, user_id, event_id, ticket_tier_id, qr_code_data, status, created_at, updated_at) VALUES ('$tkid', '$oid', '$uid', $eid, $tid, 'QR:STEP66', 'valid', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

    $pyid = 'f3000000-0000-0000-0000-000000000001';
    $db->exec("INSERT INTO payments (id, order_id, payment_intent_id, amount, currency, status, gateway, gateway_response, created_at, updated_at) VALUES ('$pyid', '$oid', 'pi-step66-xyz', 12500.75, 'NGN', 'requires_action', 'paystack', '{\"authorization_url\":\"https://example.com\"}', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");

    $orderRow = $db->querySingle("SELECT total_amount || '|' || currency || '|' || status || '|' || payment_intent_id FROM orders WHERE id = '$oid'", true);
    $ticketRow = $db->querySingle("SELECT qr_code_data || '|' || status FROM tickets WHERE id = '$tkid'", true);
    $payRow = $db->querySingle("SELECT amount || '|' || gateway || '|' || json_extract(gateway_response, '\$.authorization_url') FROM payments WHERE id='$pyid'", true);

    [$amt, $cur, $sta, $pi] = explode('|', $orderRow);
    [$qr, $tst] = explode('|', $ticketRow);
    [$pamt, $pgw, $authurl] = explode('|', $payRow);

    $pass =
        (float)$amt === 12500.75 && $cur === 'NGN' && $sta === 'pending' && $pi === 'pi-step66-xyz' &&
        $qr === 'QR:STEP66' && $tst === 'valid' &&
        (float)$pamt === 12500.75 && $pgw === 'paystack' && $authurl === 'https://example.com';
    $allPass = $allPass && $pass;
    echo "  Insert + readback all 4 tables: " . ($pass ? "PASS ✅" : "FAIL ❌") . " (amt=$amt cur=$cur status=$sta pi=$pi qr=$qr tkt_status=$tst pay_amt=$pamt pgw=$pgw auth=$authurl)" . PHP_EOL;

    $db->exec('ROLLBACK');
    echo "  ROLLBACK successful: PASS ✅" . PHP_EOL;
} catch (Throwable $e) {
    try { $db->exec('ROLLBACK'); } catch (\Throwable) {}
    echo "  FAIL ❌: " . $e::class . " " . $e->getMessage() . PHP_EOL;
    $allPass = false;
}

$db->close();

echo PHP_EOL . "=======================================" . PHP_EOL;
echo ($allPass ? "OVERALL: PASS ✅ Step 66 fully compliant" : "OVERALL: FAIL ❌") . PHP_EOL;
exit($allPass ? 0 : 1);
