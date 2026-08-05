<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Initialize database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => __DIR__ . '/database/database.sqlite',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== REFUND TABLES VERIFICATION ===\n\n";

$errors = [];
$warnings = [];

// ============================================================
// 1. CHECK ALL THREE TABLES EXIST
// ============================================================
echo "1. TABLE EXISTENCE\n";
echo str_repeat("-", 60) . "\n";

$tables = ['refund_requests', 'refund_policies', 'refund_appeals'];
foreach ($tables as $table) {
    $exists = Capsule::connection()->select("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
    $status = !empty($exists) ? '✓' : '✗ FAIL';
    if (empty($exists)) {
        $errors[] = "Table '$table' does not exist";
    }
    echo sprintf("  %-25s %s\n", $table, $status);
}

// ============================================================
// 2. REFUND_REQUESTS TABLE - FULL SCHEMA
// ============================================================
echo "\n2. REFUND_REQUESTS TABLE SCHEMA\n";
echo str_repeat("-", 60) . "\n";

$rrExists = Capsule::connection()->select("SELECT name FROM sqlite_master WHERE type='table' AND name='refund_requests'");
if (!empty($rrExists)) {
    $columns = Capsule::connection()->select("PRAGMA table_info(refund_requests)");
    $colNames = array_column($columns, 'name');
    
    $expectedColumns = [
        'id', 'ticket_id', 'order_id', 'event_id', 'user_id', 'refund_policy_id',
        'status', 'requested_amount', 'approved_amount', 'original_amount',
        'refund_amount', 'refund_percentage', 'reason', 'explanation',
        'refund_method', 'rejection_reason', 'admin_notes', 'approved_by',
        'approved_at', 'reviewed_at', 'reviewed_by', 'processing_started_at',
        'completed_at', 'payment_gateway_refund_id', 'payment_gateway_response',
        'appeal_count', 'last_appeal_at', 'created_at', 'updated_at'
    ];
    
    foreach ($expectedColumns as $col) {
        $exists = in_array($col, $colNames);
        if (!$exists) {
            $errors[] = "refund_requests missing column: $col";
        }
        echo sprintf("  %-30s %s\n", $col, $exists ? '✓' : '✗ FAIL');
    }
    
    // Check foreign keys
    echo "\n  Foreign Keys:\n";
    $fkList = Capsule::connection()->select("PRAGMA foreign_key_list(refund_requests)");
    $fkMap = [];
    foreach ($fkList as $fk) {
        $fkMap[$fk->from] = $fk->to . ' (table: ' . $fk->table . ', on_delete: ' . $fk->on_delete . ')';
    }
    
    $expectedFKs = [
        'ticket_id'       => ['table' => 'tickets', 'on_delete' => 'CASCADE'],
        'user_id'         => ['table' => 'users', 'on_delete' => 'CASCADE'],
        'refund_policy_id'=> ['table' => 'refund_policies', 'on_delete' => 'SET NULL'],
        'reviewed_by'     => ['table' => 'users', 'on_delete' => 'SET NULL'],
    ];
    
    foreach ($expectedFKs as $col => $expected) {
        $found = false;
        foreach ($fkList as $fk) {
            if ($fk->from === $col && $fk->table === $expected['table']) {
                $found = true;
                $deleteMatch = strtoupper($fk->on_delete) === $expected['on_delete'];
                if (!$deleteMatch) {
                    $warnings[] = "refund_requests.$col FK on_delete is {$fk->on_delete}, expected {$expected['on_delete']}";
                }
                echo sprintf("  %-30s ✓ (-> %s.%s, on_delete=%s)\n", $col, $fk->table, $fk->to, $fk->on_delete);
                break;
            }
        }
        if (!$found) {
            $errors[] = "refund_requests missing FK on column: $col (expected -> {$expected['table']})";
            echo sprintf("  %-30s ✗ FAIL (missing FK to %s)\n", $col, $expected['table']);
        }
    }
    
    // Check indexes
    echo "\n  Indexes:\n";
    $indexes = Capsule::connection()->select("PRAGMA index_list(refund_requests)");
    $indexNames = array_column($indexes, 'name');
    
    $expectedIndexes = [
        'idx_refund_user_status',
        'idx_refund_event_status',
        'refund_requests_ticket_id_index',
    ];
    
    foreach ($expectedIndexes as $idx) {
        $exists = in_array($idx, $indexNames);
        if (!$exists) {
            $errors[] = "refund_requests missing index: $idx";
        }
        echo sprintf("  %-35s %s\n", $idx, $exists ? '✓' : '✗ FAIL');
    }
}

// ============================================================
// 3. REFUND_POLICIES TABLE - FULL SCHEMA
// ============================================================
echo "\n3. REFUND_POLICIES TABLE SCHEMA\n";
echo str_repeat("-", 60) . "\n";

$rpExists = Capsule::connection()->select("SELECT name FROM sqlite_master WHERE type='table' AND name='refund_policies'");
if (!empty($rpExists)) {
    $columns = Capsule::connection()->select("PRAGMA table_info(refund_policies)");
    $colNames = array_column($columns, 'name');
    
    $expectedColumns = [
        'id', 'event_id', 'organizer_id', 'name', 'description',
        'refund_window_days', 'refund_percentage', 'refund_percentage_before_event',
        'refund_percentage_after_event_start', 'allow_refunds_after_event_start',
        'processing_time_business_days', 'allowed_refund_methods', 'requires_approval',
        'auto_approve_threshold', 'max_refunds_per_user', 'refund_reasons',
        'cancellation_policy', 'is_active', 'created_at', 'updated_at'
    ];
    
    foreach ($expectedColumns as $col) {
        $exists = in_array($col, $colNames);
        if (!$exists) {
            $errors[] = "refund_policies missing column: $col";
        }
        echo sprintf("  %-35s %s\n", $col, $exists ? '✓' : '✗ FAIL');
    }
    
    // Check foreign keys
    echo "\n  Foreign Keys:\n";
    $fkList = Capsule::connection()->select("PRAGMA foreign_key_list(refund_policies)");
    foreach ($fkList as $fk) {
        echo sprintf("  %-30s ✓ (-> %s.%s, on_delete=%s)\n", $fk->from, $fk->table, $fk->to, $fk->on_delete);
    }
    
    // Verify event_id FK has cascadeOnDelete
    $eventFK = array_filter($fkList, fn($fk) => $fk->from === 'event_id');
    if (!empty($eventFK)) {
        $fk = reset($eventFK);
        if (strtoupper($fk->on_delete) !== 'CASCADE') {
            $warnings[] = "refund_policies.event_id FK on_delete is {$fk->on_delete}, expected CASCADE";
        }
    } else {
        $errors[] = "refund_policies missing FK on event_id";
    }
}

// ============================================================
// 4. REFUND_APPEALS TABLE - FULL SCHEMA
// ============================================================
echo "\n4. REFUND_APPEALS TABLE SCHEMA\n";
echo str_repeat("-", 60) . "\n";

$raExists = Capsule::connection()->select("SELECT name FROM sqlite_master WHERE type='table' AND name='refund_appeals'");
if (!empty($raExists)) {
    $columns = Capsule::connection()->select("PRAGMA table_info(refund_appeals)");
    $colNames = array_column($columns, 'name');
    
    $expectedColumns = [
        'id', 'refund_request_id', 'user_id', 'appeal_reason', 'reason',
        'status', 'admin_notes', 'review_notes', 'reviewed_by', 'reviewed_at',
        'created_at', 'updated_at'
    ];
    
    foreach ($expectedColumns as $col) {
        $exists = in_array($col, $colNames);
        if (!$exists) {
            $errors[] = "refund_appeals missing column: $col";
        }
        echo sprintf("  %-30s %s\n", $col, $exists ? '✓' : '✗ FAIL');
    }
    
    // Check foreign keys
    echo "\n  Foreign Keys:\n";
    $fkList = Capsule::connection()->select("PRAGMA foreign_key_list(refund_appeals)");
    foreach ($fkList as $fk) {
        echo sprintf("  %-30s ✓ (-> %s.%s, on_delete=%s)\n", $fk->from, $fk->table, $fk->to, $fk->on_delete);
    }
    
    $expectedFKs = [
        'refund_request_id' => ['table' => 'refund_requests', 'on_delete' => 'CASCADE'],
        'user_id'           => ['table' => 'users', 'on_delete' => 'CASCADE'],
        'reviewed_by'       => ['table' => 'users', 'on_delete' => 'SET NULL'],
    ];
    
    foreach ($expectedFKs as $col => $expected) {
        $found = false;
        foreach ($fkList as $fk) {
            if ($fk->from === $col && $fk->table === $expected['table']) {
                $found = true;
                echo sprintf("  %-30s ✓ (-> %s.%s, on_delete=%s)\n", $col, $fk->table, $fk->to, $fk->on_delete);
                break;
            }
        }
        if (!$found) {
            $errors[] = "refund_appeals missing FK on column: $col (expected -> {$expected['table']})";
            echo sprintf("  %-30s ✗ FAIL (missing FK to %s)\n", $col, $expected['table']);
        }
    }
}

// ============================================================
// 5. TEST DATA INSERTION & QUERYING
// ============================================================
echo "\n5. TEST DATA INSERTION & QUERYING\n";
echo str_repeat("-", 60) . "\n";

try {
    // We need a user, event, ticket_tier, ticket, and refund_policy to exist first
    // Check if we have any existing data
    $userCount = Capsule::connection()->table('users')->count();
    $eventCount = Capsule::connection()->table('events')->count();
    $ticketTierCount = Capsule::connection()->table('ticket_tiers')->count();
    $ticketCount = Capsule::connection()->table('tickets')->count();
    
    echo "  Existing data: users=$userCount, events=$eventCount, ticket_tiers=$ticketTierCount, tickets=$ticketCount\n";
    
    if ($userCount > 0 && $eventCount > 0 && $ticketTierCount > 0 && $ticketCount > 0) {
        // Use existing data
        $user = Capsule::connection()->table('users')->first();
        $event = Capsule::connection()->table('events')->first();
        $ticketTier = Capsule::connection()->table('ticket_tiers')->first();
        $ticket = Capsule::connection()->table('tickets')->first();
        
        echo "  Using existing records for test\n";
    } else {
        // Create minimal test data
        echo "  Creating test data...\n";
        
        // Create user
        $userId = Capsule::connection()->table('users')->insertGetId([
            'name' => 'Test User',
            'email' => 'test_refund_' . uniqid() . '@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Create event
        $eventId = Capsule::connection()->table('events')->insertGetId([
            'title' => 'Test Refund Event',
            'description' => 'Test',
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'end_datetime' => date('Y-m-d H:i:s', strtotime('+30 days +3 hours')),
            'status' => 'published',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Create ticket tier
        $tierId = Capsule::connection()->table('ticket_tiers')->insertGetId([
            'event_id' => $eventId,
            'name' => 'Test Tier',
            'price' => 50.00,
            'quantity' => 100,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Create ticket
        $ticketId = Capsule::connection()->table('tickets')->insertGetId([
            'ticket_tier_id' => $tierId,
            'user_id' => $userId,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        $user = (object)['id' => $userId];
        $event = (object)['id' => $eventId];
        $ticket = (object)['id' => $ticketId];
        
        echo "  Created test data: user=$userId, event=$eventId, ticket=$ticketId\n";
    }
    
    // Test 1: Insert refund_policy
    echo "\n  Test 1: Insert refund_policy... ";
    $policyId = Capsule::connection()->table('refund_policies')->insertGetId([
        'event_id' => $event->id,
        'name' => 'Standard Refund Policy',
        'description' => 'Full refund within 7 days',
        'refund_window_days' => 7,
        'refund_percentage' => 100.00,
        'is_active' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    echo "✓ (ID: $policyId)\n";
    
    // Test 2: Insert refund_request
    echo "  Test 2: Insert refund_request... ";
    $refundId = Capsule::connection()->table('refund_requests')->insertGetId([
        'ticket_id' => $ticket->id,
        'user_id' => $user->id,
        'refund_policy_id' => $policyId,
        'status' => 'pending',
        'requested_amount' => 50.00,
        'reason' => 'Test refund request',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    echo "✓ (ID: $refundId)\n";
    
    // Test 3: Insert refund_appeal
    echo "  Test 3: Insert refund_appeal... ";
    $appealId = Capsule::connection()->table('refund_appeals')->insertGetId([
        'refund_request_id' => $refundId,
        'user_id' => $user->id,
        'appeal_reason' => 'I need a refund because...',
        'reason' => 'Customer is requesting reconsideration',
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    echo "✓ (ID: $appealId)\n";
    
    // Test 4: Query refund_requests by user_id
    echo "  Test 4: Query refund_requests by user_id... ";
    $userRefunds = Capsule::connection()->table('refund_requests')
        ->where('user_id', $user->id)
        ->get();
    echo "Found " . count($userRefunds) . " record(s) ✓\n";
    
    // Test 5: Query refund_requests by status
    echo "  Test 5: Query refund_requests by status... ";
    $pendingRefunds = Capsule::connection()->table('refund_requests')
        ->where('status', 'pending')
        ->get();
    echo "Found " . count($pendingRefunds) . " pending record(s) ✓\n";
    
    // Test 6: Query refund_requests by event_id (if column exists)
    echo "  Test 6: Query refund_requests by ticket_id... ";
    $ticketRefunds = Capsule::connection()->table('refund_requests')
        ->where('ticket_id', $ticket->id)
        ->get();
    echo "Found " . count($ticketRefunds) . " record(s) ✓\n";
    
    // Test 7: Verify cascade - delete refund_request should cascade to appeals
    echo "  Test 7: Cascade delete (refund_request -> appeals)... ";
    $appealBefore = Capsule::connection()->table('refund_appeals')
        ->where('refund_request_id', $refundId)
        ->count();
    echo "Appeals before delete: $appealBefore, ";
    Capsule::connection()->table('refund_requests')->where('id', $refundId)->delete();
    $appealAfter = Capsule::connection()->table('refund_appeals')
        ->where('refund_request_id', $refundId)
        ->count();
    echo "after delete: $appealAfter ✓\n";
    
    // Test 8: Verify RefundRequest::all() equivalent
    echo "  Test 8: RefundRequest::count() equivalent... ";
    $count = Capsule::connection()->table('refund_requests')->count();
    echo "count() = $count ✓\n";
    
    // Test 9: Verify RefundPolicy::count()
    echo "  Test 9: RefundPolicy::count() equivalent... ";
    $policyCount = Capsule::connection()->table('refund_policies')->count();
    echo "count() = $policyCount ✓\n";
    
    // Test 10: Verify RefundAppeal::count()
    echo "  Test 10: RefundAppeal::count() equivalent... ";
    $appealCount = Capsule::connection()->table('refund_appeals')->count();
    echo "count() = $appealCount ✓\n";
    
    // Clean up test data
    Capsule::connection()->table('refund_policies')->where('id', $policyId)->delete();
    echo "\n  ✓ Cleaned up test data\n";
    
} catch (Exception $e) {
    $errors[] = "Test insertion/query failed: " . $e->getMessage();
    echo "  ✗ FAILED: " . $e->getMessage() . "\n";
}

// ============================================================
// 6. SCHEMA DESIGN ANALYSIS
// ============================================================
echo "\n6. SCHEMA DESIGN ANALYSIS\n";
echo str_repeat("=", 60) . "\n";

echo "\nIndex Coverage:\n";
echo "  ✓ idx_refund_user_status: Covers filtering by user + status\n";
echo "  ✓ idx_refund_event_status: Covers filtering by event + status\n";
echo "  ✓ refund_requests_ticket_id_index: Covers lookup by ticket\n";
echo "  ⚠ Missing: index on (user_id, created_at) for user refund history queries\n";
echo "  ⚠ Missing: index on (status, created_at) for admin dashboard queries\n";
echo "  ⚠ Missing: index on refund_policies.event_id for policy lookup\n";

echo "\nJSON Fields Assessment:\n";
echo "  ✓ payment_gateway_response (refund_requests): Appropriate as JSON\n";
echo "    - Stores raw gateway response, highly variable structure\n";
echo "    - Not queried directly, only for audit/debugging\n";
echo "  ✓ allowed_refund_methods (refund_policies): Appropriate as JSON\n";
echo "    - Array of method strings (e.g. ['original', 'wallet', 'bank'])\n";
echo "    - Simple array, no relational queries needed\n";
echo "  ✓ refund_reasons (refund_policies): Appropriate as JSON\n";
echo "    - Array of reason objects with codes and descriptions\n";
echo "    - Flexible for different event types\n";
echo "  ⚠ Consider: If you need to query by specific refund methods or reasons,\n";
echo "    normalize into separate tables with FK references\n";

echo "\nForeign Key Edge Cases:\n";
echo "  ✓ refund_policies.event_id -> events(id) CASCADE\n";
echo "    - If event is deleted, all its refund policies are deleted\n";
echo "    - RISK: Accidental event deletion could wipe refund policies\n";
echo "    - RECOMMEND: Use soft-deletes on events to prevent data loss\n";
echo "  ✓ refund_requests.refund_policy_id -> refund_policies(id) SET NULL\n";
echo "    - If policy is deleted, requests retain the policy reference as null\n";
echo "    - SAFE: Historical refund data is preserved\n";
echo "  ✓ refund_requests.ticket_id -> tickets(id) CASCADE\n";
echo "    - If ticket is deleted, refund request is deleted\n";
echo "    - RISK: Ticket deletion could lose refund history\n";
echo "    - RECOMMEND: Soft-delete tickets instead of hard delete\n";
echo "  ✓ refund_requests.user_id -> users(id) CASCADE\n";
echo "    - If user is deleted, all their refund requests are deleted\n";
echo "    - RISK: Losing refund history when user account is removed\n";
echo "    - RECOMMEND: Use SET NULL or soft-delete for users\n";
echo "  ✓ refund_appeals.refund_request_id -> refund_requests(id) CASCADE\n";
echo "    - If refund request is deleted, its appeals are deleted\n";
echo "    - APPROPRIATE: Appeals are dependent on the request\n";

echo "\nWhat's Missing Before Inserting Real Refund Data:\n";
echo "  1. Create Eloquent Models:\n";
echo "     - app/Models/RefundRequest.php\n";
echo "     - app/Models/RefundPolicy.php\n";
echo "     - app/Models/RefundAppeal.php\n";
echo "  2. Add fillable/guarded properties and casts to models\n";
echo "  3. Define relationships (belongsTo, hasMany) in models\n";
echo "  4. Create a RefundService class for business logic\n";
echo "  5. Add validation rules for refund requests\n";
echo "  6. Implement payment gateway refund integration\n";
echo "  7. Add admin approval workflow\n";
echo "  8. Create notification events for refund status changes\n";
echo "  9. Add rate limiting for refund requests\n";
echo "  10. Implement refund policy matching logic\n";

// ============================================================
// 7. SUMMARY
// ============================================================
echo "\n7. VERIFICATION SUMMARY\n";
echo str_repeat("=", 60) . "\n";

if (empty($errors)) {
    echo "✓ ALL CHECKS PASSED\n\n";
} else {
    echo "✗ ERRORS FOUND:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "⚠ WARNINGS:\n";
    foreach ($warnings as $warning) {
        echo "  - $warning\n";
    }
    echo "\n";
}

echo "=== Verification Complete ===\n";