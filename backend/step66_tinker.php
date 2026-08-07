<?php

echo PHP_EOL . "=== COLUMN CHECK ===" . PHP_EOL;
$expected = [
    'orders' => ['id','user_id','event_id','total_amount','currency','status','payment_gateway','payment_intent_id','created_at','updated_at'],
    'order_items' => ['id','order_id','ticket_tier_id','quantity','unit_price','created_at','updated_at'],
    'tickets' => ['id','order_id','user_id','event_id','ticket_tier_id','qr_code_data','status','checked_in_at','created_at','updated_at'],
    'payments' => ['id','order_id','payment_intent_id','amount','currency','status','gateway','gateway_response','created_at','updated_at'],
];
$allPass = true;
foreach ($expected as $tbl => $colsExpected) {
    $colsActual = array_column(DB::select("PRAGMA table_info({$tbl})"), 'name');
    $missing = array_diff($colsExpected, $colsActual);
    $pass = empty($missing);
    $allPass = $allPass && $pass;
    echo "[$tbl] " . ($pass ? "PASS" : "FAIL missing: " . implode(',', $missing)) . " cols=" . count($colsActual) . PHP_EOL;
}

echo PHP_EOL . "=== INDEX CHECK ===" . PHP_EOL;
$expectedIdx = [
    'orders' => ['orders_user_id_index','orders_status_index','orders_payment_intent_id_unique'],
    'order_items' => ['order_items_order_id_index'],
    'tickets' => ['tickets_user_id_index','tickets_event_id_index'],
    'payments' => ['payments_order_id_index'],
];
foreach ($expectedIdx as $tbl => $idx) {
    $actual = array_map('strtolower', array_column(DB::select("PRAGMA index_list({$tbl})"), 'name'));
    foreach ($idx as $needle) {
        $found = in_array(strtolower($needle), $actual, true);
        $allPass = $allPass && $found;
        echo "  [$tbl] $needle : " . ($found ? "OK" : "MISSING") . PHP_EOL;
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
    $actual = DB::select("PRAGMA foreign_key_list({$tbl})");
    foreach ($rules as $r) {
        $found = false;
        foreach ($actual as $a) {
            if ($a->from === $r['from'] && $a->table === $r['table']) {
                $found = true;
                echo "  [$tbl] {$r['from']}->{$r['table']} (ON DELETE {$a->on_delete}) OK" . PHP_EOL;
                break;
            }
        }
        if (!$found) {
            $allPass = false;
            echo "  [$tbl] {$r['from']}->{$r['table']}.id : MISSING" . PHP_EOL;
        }
    }
}

echo PHP_EOL . "=== TEST INSERT (FULL ROLLBACK) ===" . PHP_EOL;
try {
    DB::beginTransaction();
    $realUser = DB::table('users')->select('id')->first();
    $realEvent = DB::table('events')->select('id')->first();
    $realTier = DB::table('ticket_tiers')->select('id')->first();

    $uid = $realUser ? $realUser->id : null;
    $eid = $realEvent ? $realEvent->id : null;
    $tid = $realTier ? $realTier->id : null;

    if (!$uid || !$eid || !$tid) {
        DB::rollBack();
        echo "  SKIP: users/events/ticket_tiers tables empty (need seed data for FK insert test). Found users=" . (int)$realUser . " events=" . (int)$realEvent . " tiers=" . (int)$realTier . PHP_EOL;
    } else {
        $oid = 'aa000000-0000-0000-0000-000000000001';
        DB::insert("INSERT INTO orders (id, user_id, event_id, total_amount, currency, status, created_at, updated_at) VALUES (?, ?, ?, 12345.67, 'NGN', 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)", [$oid, $uid, $eid]);

        $oiid = 'bb000000-0000-0000-0000-000000000001';
        DB::insert("INSERT INTO order_items (id, order_id, ticket_tier_id, quantity, unit_price, created_at, updated_at) VALUES (?, ?, ?, 5, 2469.13, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)", [$oiid, $oid, $tid]);

        $tkid = 'cc000000-0000-0000-0000-000000000001';
        DB::insert("INSERT INTO tickets (id, order_id, user_id, event_id, ticket_tier_id, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'valid', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)", [$tkid, $oid, $uid, $eid, $tid]);

        $pyid = 'dd000000-0000-0000-0000-000000000001';
        DB::insert("INSERT INTO payments (id, order_id, payment_intent_id, amount, currency, status, gateway, gateway_response, created_at, updated_at) VALUES (?, ?, 'pi-step66-test', 12345.67, 'NGN', 'succeeded', 'paystack', '{\"ref\":\"abc123\"}', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)", [$pyid, $oid]);

        $order = DB::select("SELECT id, total_amount, currency, status, user_id, event_id FROM orders WHERE id = ?", [$oid])[0];
        $ticket = DB::select("SELECT id, status FROM tickets WHERE id = ?", [$tkid])[0];
        $payment = DB::select("SELECT id, payment_intent_id, amount, gateway, json_extract(gateway_response, '\$.ref') AS gw_ref FROM payments WHERE id = ?", [$pyid])[0];

        $passInsert =
            $order->total_amount == 12345.67 &&
            $order->currency === 'NGN' &&
            $order->status === 'pending' &&
            $ticket->status === 'valid' &&
            $payment->amount == 12345.67 &&
            $payment->gateway === 'paystack' &&
            $payment->gw_ref === 'abc123';
        $allPass = $allPass && $passInsert;
        echo "  Insert+readback 4 tables (orders, order_items, tickets, payments) with real FKs : " . ($passInsert ? "PASS (amount=$order->total_amount status=$order->status)" : "FAIL") . PHP_EOL;
        DB::rollBack();
        echo "  Transaction rolled back cleanly: PASS" . PHP_EOL;
    }
} catch (\Throwable $e) {
    try { DB::rollBack(); } catch (\Throwable) {}
    echo "  INSERT FAIL: " . $e::class . " " . $e->getMessage() . PHP_EOL;
    $allPass = false;
}

echo PHP_EOL . ($allPass ? "OVERALL: PASS" : "OVERALL: FAIL") . PHP_EOL;
