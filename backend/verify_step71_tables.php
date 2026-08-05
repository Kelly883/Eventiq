<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Step 71 Database Verification ===\n\n";

// Check tables exist
$tables = ['tickets', 'fraud_events', 'audit_logs', 'ticket_inventory', 'analytics_events_metrics'];
echo "1. TABLE EXISTENCE:\n";
foreach ($tables as $table) {
    $exists = Schema::hasTable($table);
    echo "   $table: " . ($exists ? "EXISTS" : "MISSING") . "\n";
}

echo "\n2. INDEXES:\n";

// Check tickets indexes
if (Schema::hasTable('tickets')) {
    $indexes = DB::select("PRAGMA index_list('tickets')");
    $indexNames = array_column($indexes, 'name');
    echo "   tickets indexes:\n";
    echo "     - idx_tickets_event_status: " . (in_array('idx_tickets_event_status', $indexNames) ? "YES" : "NO") . "\n";
    echo "     - idx_tickets_event_checked_in: " . (in_array('idx_tickets_event_checked_in', $indexNames) ? "YES" : "NO") . "\n";
}

// Check fraud_events indexes
if (Schema::hasTable('fraud_events')) {
    $indexes = DB::select("PRAGMA index_list('fraud_events')");
    $indexNames = array_column($indexes, 'name');
    echo "   fraud_events indexes:\n";
    echo "     - idx_fraud_ticket_event: " . (in_array('idx_fraud_ticket_event', $indexNames) ? "YES" : "NO") . "\n";
    echo "     - idx_fraud_event_detected: " . (in_array('idx_fraud_event_detected', $indexNames) ? "YES" : "NO") . "\n";
}

// Check audit_logs indexes
if (Schema::hasTable('audit_logs')) {
    $indexes = DB::select("PRAGMA index_list('audit_logs')");
    $indexNames = array_column($indexes, 'name');
    echo "   audit_logs indexes:\n";
    echo "     - idx_audit_logs_event_created: " . (in_array('idx_audit_logs_event_created', $indexNames) ? "YES" : "NO") . "\n";
}

// Check ticket_inventory indexes
if (Schema::hasTable('ticket_inventory')) {
    $indexes = DB::select("PRAGMA index_list('ticket_inventory')");
    $indexNames = array_column($indexes, 'name');
    echo "   ticket_inventory indexes:\n";
    echo "     - idx_ticket_inventory_event: " . (in_array('idx_ticket_inventory_event', $indexNames) ? "YES" : "NO") . "\n";
}

// Check analytics_events_metrics indexes
if (Schema::hasTable('analytics_events_metrics')) {
    $indexes = DB::select("PRAGMA index_list('analytics_events_metrics')");
    $indexNames = array_column($indexes, 'name');
    echo "   analytics_events_metrics indexes:\n";
    echo "     - idx_analytics_event_id: " . (in_array('idx_analytics_event_id', $indexNames) ? "YES" : "NO") . "\n";
}

echo "\n3. FOREIGN KEYS:\n";

$tablesWithFKs = [
    'tickets' => ['checked_in_by'],
    'fraud_events' => ['ticket_id', 'event_id', 'first_check_in_by', 'second_check_in_by'],
    'audit_logs' => ['event_id', 'ticket_id'],
    'ticket_inventory' => ['event_id'],
];

foreach ($tablesWithFKs as $table => $columns) {
    if (!Schema::hasTable($table)) continue;
    
    $foreignKeys = DB::select("PRAGMA foreign_key_list('$table')");
    $fkColumns = array_unique(array_column($foreignKeys, 'from'));
    
    echo "   $table:\n";
    foreach ($columns as $column) {
        $hasFK = in_array($column, $fkColumns);
        echo "     - $column: " . ($hasFK ? "FK exists" : "FK missing") . "\n";
    }
}

echo "\n4. COLUMNS:\n";

if (Schema::hasTable('tickets')) {
    $columns = Schema::getColumnListing('tickets');
    $required = ['ticket_id', 'attendee_name', 'attendee_email', 'tier', 'status', 'checked_in_at', 'checked_in_by'];
    echo "   tickets:\n";
    foreach ($required as $col) {
        echo "     - $col: " . (in_array($col, $columns) ? "YES" : "NO") . "\n";
    }
}

if (Schema::hasTable('fraud_events')) {
    $columns = Schema::getColumnListing('fraud_events');
    $required = ['fraud_type', 'risk_level', 'notes', 'first_check_in_at', 'first_check_in_by', 'second_check_in_at', 'second_check_in_by'];
    echo "   fraud_events:\n";
    foreach ($required as $col) {
        echo "     - $col: " . (in_array($col, $columns) ? "YES" : "NO") . "\n";
    }
}

if (Schema::hasTable('audit_logs')) {
    $columns = Schema::getColumnListing('audit_logs');
    $required = ['event_id', 'user_id', 'ticket_id', 'details'];
    echo "   audit_logs:\n";
    foreach ($required as $col) {
        echo "     - $col: " . (in_array($col, $columns) ? "YES" : "NO") . "\n";
    }
}

if (Schema::hasTable('ticket_inventory')) {
    $columns = Schema::getColumnListing('ticket_inventory');
    $required = ['total_available', 'total_checked_in', 'total_void'];
    echo "   ticket_inventory:\n";
    foreach ($required as $col) {
        echo "     - $col: " . (in_array($col, $columns) ? "YES" : "NO") . "\n";
    }
}

if (Schema::hasTable('analytics_events_metrics')) {
    $columns = Schema::getColumnListing('analytics_events_metrics');
    $required = ['total_tickets_sold', 'total_checked_in', 'check_in_rate'];
    echo "   analytics_events_metrics:\n";
    foreach ($required as $col) {
        echo "     - $col: " . (in_array($col, $columns) ? "YES" : "NO") . "\n";
    }
}

echo "\nVerification complete\n";

</parameter>
<command>php backend/verify_step71_tables.php</command>
<requires_approval>false</requires_approval>
</execute_command>