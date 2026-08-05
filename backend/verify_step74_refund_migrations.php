<?php

/**
 * Step 74 Verification: Refund Tables Migration Check
 *
 * Bootstraps the full Laravel application to verify:
 *  - All three refund tables exist with correct schema
 *  - Indexes are in place
 *  - Foreign key constraints are correct
 *  - Eloquent models can query each table
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Features\Refunds\Models\RefundRequest;
use App\Features\Refunds\Models\RefundPolicy;
use App\Features\Refunds\Models\RefundAppeal;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$driver = DB::getDriverName();
echo "=== STEP 74: REFUND MIGRATIONS VERIFICATION ===\n";
echo "Database driver: $driver\n";
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
    $exists = Schema::hasTable($table);
    $status = $exists ? 'OK' : 'FAIL';
    if (!$exists) {
        $errors[] = "Table '$table' does not exist";
    } else {
        $passed[] = "Table '$table' exists";
    }
    echo sprintf("  %-25s %s\n", $table, $status);
}

// ---------------------------------------------------------------------------
// Helper: get column details (cross-driver)
// ---------------------------------------------------------------------------
function getColumnDetails(string $table): array
{
    $driver = DB::getDriverName();
    $details = [];

    if ($driver === 'sqlite') {
        $rows = DB::select("PRAGMA table_info($table)");
        foreach ($rows as $row) {
            $details[$row->name] = [
                'type'     => $row->type,
                'nullable' => !$row->notnull,
                'default'  => $row->dflt_value,
                'pk'       => (bool) $row->pk,
            ];
        }
    } else {
        $rows = DB::select(
            "SELECT column_name, data_type, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ?
             ORDER BY ordinal_position",
            [$table]
        );
        foreach ($rows as $row) {
            $details[$row->column_name] = [
                'type'     => $row->data_type,
                'nullable' => strtoupper($row->is_nullable) === 'YES',
                'default'  => $row->column_default,
                'pk'       => false,
            ];
        }
        $pkRows = DB::select(
            "SELECT column_name FROM information_schema.key_column_usage
             WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = 'PRIMARY'",
            [$table]
        );
        foreach ($pkRows as $pk) {
            $details[$pk->column_name]['pk'] = true;
        }
    }

    return $details;
}

function getIndexes(string $table): array
{
    $driver = DB::getDriverName();
    $out = [];

    if ($driver === 'sqlite') {
        $indexes = DB::select("PRAGMA index_list($table)");
        foreach ($indexes as $idx) {
            $info = DB::select("PRAGMA index_info($idx->name)");
            $cols = array_map(fn($i) => $i->name, $info);
            $out[$idx->name] = $cols;
        }
    } else {
        $rows = DB::select(
            "SELECT s.index_name, GROUP_CONCAT(s.column_name ORDER BY s.seq_in_index) AS cols
             FROM information_schema.statistics s
             WHERE s.table_schema = DATABASE() AND s.table_name = ?
             GROUP BY s.index_name",
            [$table]
        );
        foreach ($rows as $row) {
            $out[$row->index_name] = explode(',', $row->cols);
        }
    }

    return $out;
}

function getForeignKeys(string $table): array
{
    $driver = DB::getDriverName();
    $out = [];

    if ($driver === 'sqlite') {
        $fks = DB::select("PRAGMA foreign_key_list($table)");
        foreach ($fks as $fk) {
            $out[$fk->from] = [
                'table'     => $fk->table,
                'column'    => $fk->to,
                'on_delete' => strtoupper($fk->on_delete ?: 'NO ACTION'),
            ];
        }
    } else {
        $rows = DB::select(
            "SELECT kcu.column_name, kcu.referenced_table_name, kcu.referenced_column_name,
                    rc.delete_rule
             FROM information_schema.key_column_usage kcu
             JOIN information_schema.referential_constraints rc
               ON kcu.constraint_name = rc.constraint_name
              AND kcu.table_schema = rc.constraint_schema
             WHERE kcu.table_schema = DATABASE()
               AND kcu.table_name = ?
               AND kcu.referenced_table_name IS NOT NULL",
            [$table]
        );
        foreach ($rows as $row) {
            $out[$row->column_name] = [
                'table'     => $row->referenced_table_name,
                'column'    => $row->referenced_column_name,
                'on_delete' => strtoupper($row->delete_rule),
            ];
        }
    }

    return $out;
}

// ---------------------------------------------------------------------------
// 2. REFUND_REQUESTS SCHEMA
// ---------------------------------------------------------------------------
echo "\n2. REFUND_REQUESTS TABLE SCHEMA\n";
echo str_repeat('-', 70) . "\n";

if (Schema::hasTable('refund_requests')) {
    $cols = getColumnDetails('refund_requests');

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
        $extra = $exists ? " ({$cols[$col]['type']})" : '';
        echo sprintf("  %-32s %s%s\n", $col, $exists ? 'OK' : 'FAIL', $extra);
    }

    echo "\n  Indexes:\n";
    $indexes = getIndexes('refund_requests');
    $expectedIndexes = [
        'idx_refund_user_status'          => ['user_id', 'status'],
        'idx_refund_event_status'         => ['event_id', 'status'],
        'refund_requests_ticket_id_index' => ['ticket_id'],
    ];

    foreach ($expectedIndexes as $name => $expectedCols) {
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
    $fks = getForeignKeys('refund_requests');
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
        $onDeleteMatch = $found['on_delete'] === $expect['on_delete'];
        if (!$onDeleteMatch) {
            $warnings[] = "refund_requests.$col on_delete={$found['on_delete']} (expected {$expect['on_delete']})";
        }
        $passed[] = "refund_requests FK $col -> {$expect['table']}";
        echo sprintf("  %-32s OK (-> %s.%s, on_delete=%s)\n",
            $col, $found['table'], $found['column'], $found['on_delete']);
    }
}

// ---------------------------------------------------------------------------
// 3. REFUND_POLICIES SCHEMA
// ---------------------------------------------------------------------------
echo "\n3. REFUND_POLICIES TABLE SCHEMA\n";
echo str_repeat('-', 70) . "\n";

if (Schema::hasTable('refund_policies')) {
    $cols = getColumnDetails('refund_policies');

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
        $extra = $exists ? " ({$cols[$col]['type']})" : '';
        echo sprintf("  %-38s %s%s\n", $col, $exists ? 'OK' : 'FAIL', $extra);
    }

    echo "\n  Foreign Keys:\n";
    $fks = getForeignKeys('refund_policies');
    foreach ($fks as $col => $fk) {
        echo sprintf("  %-32s OK (-> %s.%s, on_delete=%s)\n", $col, $fk['table'], $fk['column'], $fk['on_delete']);
    }
    if (!isset($fks['event_id'])) {
        $errors[] = "refund_policies missing FK on event_id";
        echo "  event_id                        FAIL (missing FK)\n";
    } else {
        $passed[] = "refund_policies FK event_id -> events";
        if ($fks['event_id']['on_delete'] !== 'CASCADE') {
            $warnings[] = "refund_policies.event_id on_delete={$fks['event_id']['on_delete']} (expected CASCADE)";
        }
    }

    echo "\n  Indexes:\n";
    $indexes = getIndexes('refund_policies');
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

if (Schema::hasTable('refund_appeals')) {
    $cols = getColumnDetails('refund_appeals');

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
        $extra = $exists ? " ({$cols[$col]['type']})" : '';
        echo sprintf("  %-32s %s%s\n", $col, $exists ? 'OK' : 'FAIL', $extra);
    }

    echo "\n  Foreign Keys:\n";
    $fks = getForeignKeys('refund_appeals');
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
        echo sprintf("  %-32s OK (-> %s.%s, on_delete=%s)\n",
            $col, $found['table'], $found['column'], $found['on_delete']);
    }

    echo "\n  Indexes:\n";
    $indexes = getIndexes('refund_appeals');
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
// 5. ELOQUENT MODEL QUERIES (counts should be 0)
// ---------------------------------------------------------------------------
echo "\n5. ELOQUENT MODEL QUERIES\n";
echo str_repeat('-', 70) . "\n";

try {
    $rrCount = RefundRequest::count();
    echo sprintf("  RefundRequest::count()  = %d %s\n", $rrCount, $rrCount === 0 ? 'OK' : '(expected 0)');
    if ($rrCount !== 0) {
        $warnings[] = "RefundRequest::count() = $rrCount (expected 0 on fresh migration)";
    } else {
        $passed[] = "RefundRequest::count() returns 0";
    }
} catch (Throwable $e) {
    $errors[] = "RefundRequest::count() failed: " . $e->getMessage();
    echo "  RefundRequest::count()  FAIL: " . $e->getMessage() . "\n";
}

try {
    $rpCount = RefundPolicy::count();
    echo sprintf("  RefundPolicy::count()   = %d %s\n", $rpCount, $rpCount === 0 ? 'OK' : '(expected 0)');
    if ($rpCount !== 0) {
        $warnings[] = "RefundPolicy::count() = $rpCount (expected 0 on fresh migration)";
    } else {
        $passed[] = "RefundPolicy::count() returns 0";
    }
} catch (Throwable $e) {
    $errors[] = "RefundPolicy::count() failed: " . $e->getMessage();
    echo "  RefundPolicy::count()   FAIL: " . $e->getMessage() . "\n";
}

try {
    $raCount = RefundAppeal::count();
    echo sprintf("  RefundAppeal::count()   = %d %s\n", $raCount, $raCount === 0 ? 'OK' : '(expected 0)');
    if ($raCount !== 0) {
        $warnings[] = "RefundAppeal::count() = $raCount (expected 0 on fresh migration)";
    } else {
        $passed[] = "RefundAppeal::count() returns 0";
    }
} catch (Throwable $e) {
    $errors[] = "RefundAppeal::count() failed: " . $e->getMessage();
    echo "  RefundAppeal::count()   FAIL: " . $e->getMessage() . "\n";
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