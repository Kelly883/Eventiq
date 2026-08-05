<?php

/**
 * Step 74 - Simple verification using PDO directly (no Laravel bootstrap).
 * Avoids the slow bootstrap that causes timeouts.
 */

$dbPath = __DIR__ . '/database/database.sqlite';
if (!file_exists($dbPath)) {
    echo "FAIL: database.sqlite not found at $dbPath\n";
    exit(1);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== STEP 74: REFUND MIGRATIONS VERIFICATION (PDO) ===\n";
echo "Database: $dbPath\n";
echo str_repeat('=', 70) . "\n\n";

$errors = [];
$warnings = [];
$passed = [];

// ---------------------------------------------------------------------------
// 1. TABLE EXISTENCE
// ---------------------------------------------------------------------------
echo "1. TABLE EXISTENCE\n";
echo str_repeat('-', 70) . "\n";

$tables = ['refund_requests', 'refund_policies', 'refund_appeals'];
foreach ($tables as $table) {
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
    $exists = $stmt->fetch() !== false;
    if (!$exists) {
        $errors[] = "Table '$table' does not exist";
    } else {
        $passed[] = "Table '$table' exists";
    }
    echo sprintf("  %-25s %s\n", $table, $exists ? 'OK' : 'FAIL');
}

// ---------------------------------------------------------------------------
// Helper functions
// ---------------------------------------------------------------------------
function getColumns(PDO $pdo, string $table): array
{
    $stmt = $pdo->query("PRAGMA table_info($table)");
    $cols = [];
    while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
        $cols[$row->name] = $row;
    }
    return $cols;
}

function getIndexes(PDO $pdo, string $table): array
{
    $stmt = $pdo->query("PRAGMA index_list($table)");
    $indexes = [];
    while ($idx = $stmt->fetch(PDO::FETCH_OBJ)) {
        $infoStmt = $pdo->query("PRAGMA index_info($idx->name)");
        $cols = [];
        while ($info = $infoStmt->fetch(PDO::FETCH_OBJ)) {
            $cols[] = $info->name;
        }
        $indexes[$idx->name] = $cols;
    }
    return $indexes;
}

function getForeignKeys(PDO $pdo, string $table): array
{
    $stmt = $pdo->query("PRAGMA foreign_key_list($table)");
    $fks = [];
    while ($fk = $stmt->fetch(PDO::FETCH_OBJ)) {
        $fks[$fk->from] = $fk;
    }
    return $fks;
}

// ---------------------------------------------------------------------------
// 2. REFUND_REQUESTS SCHEMA
// ---------------------------------------------------------------------------
echo "\n2. REFUND_REQUESTS TABLE SCHEMA\n";
echo str_repeat('-', 70) . "\n";

$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='refund_requests'");
if ($stmt->fetch()) {
    $cols = getColumns($pdo, 'refund_requests');

    $expected = [
        'id', 'ticket_id', 'order_id', 'event_id', 'user_id', 'refund_policy_id',
        'status', 'requested_amount', 'approved_amount', 'original_amount',
        'refund_amount', 'refund_percentage', 'reason', 'explanation',
        'refund_method', 'rejection_reason', 'admin_notes', 'approved_by',
        'approved_at', 'reviewed_at', 'reviewed_by', 'processing_started_at',
        'completed_at', 'payment_gateway_refund_id', 'payment_gateway_response',
        'appeal_count', 'last_appeal_at', 'created_at', 'updated_at',
    ];

    foreach ($expected as $col) {
        $exists = array_key_exists($col, $cols);
        if (!$exists) {
            $errors[] = "refund_requests missing column: $col";
        } else {
            $passed[] = "refund_requests.$col present";
        }
        $extra = $exists ? " ({$cols[$col]->type})" : '';
        echo sprintf("  %-32s %s%s\n", $col, $exists ? 'OK' : 'FAIL', $extra);
    }

    echo "\n  Indexes:\n";
    $indexes = getIndexes($pdo, 'refund_requests');
    $expectedIndexes = ['idx_refund_user_status', 'idx_refund_event_status', 'refund_requests_ticket_id_index'];
    foreach ($expectedIndexes as $name) {
        $exists = array_key_exists($name, $indexes);
        if (!$exists) {
            $errors[] = "refund_requests missing index: $name";
        } else {
            $passed[] = "refund_requests index $name present";
        }
        $actual = $exists ? implode(', ', $indexes[$name]) : 'N/A';
        echo sprintf("  %-38s %s [%s]\n", $name, $exists ? 'OK' : 'FAIL', $actual);
    }

    echo "\n  Foreign Keys:\n";
    $fks = getForeignKeys($pdo, 'refund_requests');
    $expectedFKs = [
        'ticket_id'        => ['table' => 'tickets', 'on_delete' => 'CASCADE'],
        'user_id'          => ['table' => 'users', 'on_delete' => 'CASCADE'],
        'refund_policy_id' => ['table' => 'refund_policies', 'on_delete' => 'SET NULL'],
        'reviewed_by'      => ['table' => 'users', 'on_delete' => 'SET NULL'],
    ];

    foreach ($expectedFKs as $col => $expect) {
        $found = $fks[$col] ?? null;
        if (!$found) {
            $errors[] = "refund_requests missing FK on $col";
            echo sprintf("  %-32s FAIL (missing FK to %s)\n", $col, $expect['table']);
            continue;
        }
        $onDelete = strtoupper($found->on_delete ?: 'NO ACTION');
        if ($onDelete !== $expect['on_delete']) {
            $warnings[] = "refund_requests.$col on_delete=$onDelete (expected {$expect['on_delete']})";
        }
        $passed[] = "refund_requests FK $col -> {$expect['table']}";
        echo sprintf("  %-32s OK (-> %s.%s, on_delete=%s)\n", $col, $found->table, $found->to, $onDelete);
    }
}

// ---------------------------------------------------------------------------
// 3. REFUND_POLICIES SCHEMA
// ---------------------------------------------------------------------------
echo "\n3. REFUND_POLICIES TABLE SCHEMA\n";
echo str_repeat('-', 70) . "\n";

$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='refund_policies'");
if ($stmt->fetch()) {
    $cols = getColumns($pdo, 'refund_policies');

    $expected = [
        'id', 'event_id', 'organizer_id', 'name', 'description',
        'refund_window_days', 'refund_percentage', 'refund_percentage_before_event',
        'refund_percentage_after_event_start', 'allow_refunds_after_event_start',
        'processing_time_business_days', 'allowed_refund_methods', 'requires_approval',
        'auto_approve_threshold', 'max_refunds_per_user', 'refund_reasons',
        'cancellation_policy', 'is_active', 'created_at', 'updated_at',
    ];

    foreach ($expected as $col) {
        $exists = array_key_exists($col, $cols);
        if (!$exists) {
            $errors[] = "refund_policies missing column: $col";
        } else {
            $passed[] = "refund_policies.$col present";
        }
        $extra = $exists ? " ({$cols[$col]->type})" : '';
        echo sprintf("  %-38s %s%s\n", $col, $exists ? 'OK' : 'FAIL', $extra);
    }

    echo "\n  Foreign Keys:\n";
    $fks = getForeignKeys($pdo, 'refund_policies');
    foreach ($fks as $col => $fk) {
        echo sprintf("  %-32s OK (-> %s.%s, on_delete=%s)\n", $col, $fk->table, $fk->to, strtoupper($fk->on_delete ?: 'NO ACTION'));
    }
    if (!isset($fks['event_id'])) {
        $errors[] = "refund_policies missing FK on event_id";
        echo "  event_id                        FAIL (missing FK)\n";
    } else {
        $passed[] = "refund_policies FK event_id -> events";
        if (strtoupper($fks['event_id']->on_delete) !== 'CASCADE') {
            $warnings[] = "refund_policies.event_id on_delete not CASCADE";
        }
    }

    echo "\n  Indexes:\n";
    $indexes = getIndexes($pdo, 'refund_policies');
    if (empty($indexes)) {
        $warnings[] = "refund_policies has no indexes (event_id lookup will scan)";
        echo "  (none) - WARNING: no indexes defined\n";
    } else {
        foreach ($indexes as $name => $cols2) {
            echo sprintf("  %-38s OK [%s]\n", $name, implode(', ', $cols2));
        }
    }
}

// ---------------------------------------------------------------------------
// 4. REFUND_APPEALS SCHEMA
// ---------------------------------------------------------------------------
echo "\n4. REFUND_APPEALS TABLE SCHEMA\n";
echo str_repeat('-', 70) . "\n";

$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='refund_appeals'");
if ($stmt->fetch()) {
    $cols = getColumns($pdo, 'refund_appeals');

    $expected = [
        'id', 'refund_request_id', 'user_id', 'appeal_reason', 'reason',
        'status', 'admin_notes', 'review_notes', 'reviewed_by', 'reviewed_at',
        'created_at', 'updated_at',
    ];

    foreach ($expected as $col) {
        $exists = array_key_exists($col, $cols);
        if (!$exists) {
            $errors[] = "refund_appeals missing column: $col";
        } else {
            $passed[] = "refund_appeals.$col present";
        }
        $extra = $exists ? " ({$cols[$col]->type})" : '';
        echo sprintf("  %-32s %s%s\n", $col, $exists ? 'OK' : 'FAIL', $extra);
    }

    echo "\n  Foreign Keys:\n";
    $fks = getForeignKeys($pdo, 'refund_appeals');
    $expectedFKs = [
        'refund_request_id' => ['table' => 'refund_requests', 'on_delete' => 'CASCADE'],
        'user_id'           => ['table' => 'users', 'on_delete' => 'CASCADE'],
        'reviewed_by'       => ['table' => 'users', 'on_delete' => 'SET NULL'],
    ];

    foreach ($expectedFKs as $col => $expect) {
        $found = $fks[$col] ?? null;
        if (!$found) {
            $errors[] = "refund_appeals missing FK on $col";
            echo sprintf("  %-32s FAIL (missing FK to %s)\n", $col, $expect['table']);
            continue;
        }
        $passed[] = "refund_appeals FK $col -> {$expect['table']}";
        echo sprintf("  %-32s OK (-> %s.%s, on_delete=%s)\n", $col, $found->table, $found->to, strtoupper($found->on_delete ?: 'NO ACTION'));
    }

    echo "\n  Indexes:\n";
    $indexes = getIndexes($pdo, 'refund_appeals');
    if (empty($indexes)) {
        $warnings[] = "refund_appeals has no indexes (refund_request_id lookup will scan)";
        echo "  (none) - WARNING: no indexes defined\n";
    } else {
        foreach ($indexes as $name => $cols2) {
            echo sprintf("  %-38s OK [%s]\n", $name, implode(', ', $cols2));
        }
    }
}

// ---------------------------------------------------------------------------
// 5. ROW COUNTS (should be 0)
// ---------------------------------------------------------------------------
echo "\n5. ROW COUNTS (should be 0 on fresh migration)\n";
echo str_repeat('-', 70) . "\n";

foreach ($tables as $table) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
    $count = $stmt->fetchColumn();
    $ok = $count == 0;
    if (!$ok) {
        $warnings[] = "$table has $count rows (expected 0)";
    } else {
        $passed[] = "$table count = 0";
    }
    echo sprintf("  %-25s %d %s\n", $table, $count, $ok ? 'OK' : '(expected 0)');
}

// ---------------------------------------------------------------------------
// 6. SUMMARY
// ---------------------------------------------------------------------------
echo "\n6. SUMMARY\n";
echo str_repeat('=', 70) . "\n";

echo "\nPassed checks: " . count($passed) . "\n";
echo "Warnings: " . count($warnings) . "\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nERRORS:\n";
    foreach ($errors as $e) {
        echo "  - $e\n";
    }
}

if (!empty($warnings)) {
    echo "\nWARNINGS:\n";
    foreach ($warnings as $w) {
        echo "  - $w\n";
    }
}

echo "\n" . (empty($errors) ? "RESULT: ALL CRITICAL CHECKS PASSED" : "RESULT: CRITICAL ERRORS FOUND") . "\n";
echo "=== Verification Complete ===\n";