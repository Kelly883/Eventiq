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

echo "=== Verifying Steps 72-75 Tables ===\n\n";

// Check if tables exist using raw query
$tables = Capsule::connection()->select("SELECT name FROM sqlite_master WHERE type='table'");
$tableNames = array_column($tables, 'name');

$requiredTables = [
    'email_templates',
    'audit_logs',
    'delivery_preferences',
    'push_notification_devices',
    'push_notification_templates',
    'refund_requests',
    'refund_policies',
    'refund_appeals',
    'settlement_policies',
    'payouts',
    'payout_calculations'
];

echo "Table Existence Check:\n";
echo str_repeat("-", 50) . "\n";

foreach ($requiredTables as $table) {
    $exists = in_array($table, $tableNames);
    echo sprintf("%-35s %s\n", $table, $exists ? '✓ EXISTS' : '✗ MISSING');
}
echo "\n";

// Helper function to get columns
$getColumns = function($table) {
    return Capsule::connection()->select("PRAGMA table_info($table)");
};

// Verify email_templates structure (Step 72)
echo "email_templates Structure:\n";
echo str_repeat("-", 50) . "\n";
$columns = $getColumns('email_templates');
$columnNames = array_column($columns, 'name');
$expectedColumns = ['id', 'name', 'subject', 'body', 'mjml_source', 'type', 'html_body', 'mjml_body', 'variables', 'is_active', 'created_at', 'updated_at'];
foreach ($expectedColumns as $col) {
    $exists = in_array($col, $columnNames);
    echo sprintf("  %-30s %s\n", $col, $exists ? '✓' : '✗');
}

// Check index on (type, is_active)
echo "\nIndexes on email_templates:\n";
$indexes = Capsule::connection()->select("PRAGMA index_list(email_templates)");
$indexFound = false;
foreach ($indexes as $index) {
    if ($index->name === 'idx_email_templates_type_active') {
        $indexFound = true;
        break;
    }
}
echo $indexFound ? "  ✓ Index idx_email_templates_type_active exists\n" : "  ✗ Index idx_email_templates_type_active missing\n";

// Verify audit_logs has metadata column (Step 72)
echo "\naudit_logs.metadata Column:\n";
echo str_repeat("-", 50) . "\n";
$columns = $getColumns('audit_logs');
$columnNames = array_column($columns, 'name');
echo in_array('metadata', $columnNames) ? "  ✓ metadata column exists\n" : "  ✗ metadata column missing\n";

// Verify delivery_preferences fields (Steps 72 & 73)
echo "\ndelivery_preferences Email & Push Fields:\n";
echo str_repeat("-", 50) . "\n";
$columns = $getColumns('delivery_preferences');
$columnNames = array_column($columns, 'name');
$deliveryFields = ['event_cancellations', 'refund_confirmations', 'promotional_offers', 'push_notifications_enabled', 'push_order_confirmation', 'push_event_reminder', 'push_checkin_alert', 'push_promotional_offers'];
foreach ($deliveryFields as $field) {
    $exists = in_array($field, $columnNames);
    echo sprintf("  %-30s %s\n", $field, $exists ? '✓' : '✗');
}

// Verify push_notification_devices structure (Step 73)
echo "\npush_notification_devices Structure:\n";
echo str_repeat("-", 50) . "\n";
$columns = $getColumns('push_notification_devices');
$columnNames = array_column($columns, 'name');
$expectedColumns = ['id', 'user_id', 'token', 'provider', 'device_type', 'created_at', 'updated_at'];
foreach ($expectedColumns as $col) {
    $exists = in_array($col, $columnNames);
    echo sprintf("  %-30s %s\n", $col, $exists ? '✓' : '✗');
}

// Verify push_notification_templates structure (Step 73)
echo "\npush_notification_templates Structure:\n";
echo str_repeat("-", 50) . "\n";
$columns = $getColumns('push_notification_templates');
$columnNames = array_column($columns, 'name');
$expectedColumns = ['id', 'name', 'type', 'title', 'body', 'variables', 'is_active', 'created_at', 'updated_at'];
foreach ($expectedColumns as $col) {
    $exists = in_array($col, $columnNames);
    echo sprintf("  %-30s %s\n", $col, $exists ? '✓' : '✗');
}

// Verify refund_requests structure (Step 74)
echo "\nrefund_requests Structure:\n";
echo str_repeat("-", 50) . "\n";
$columns = $getColumns('refund_requests');
$columnNames = array_column($columns, 'name');
$expectedColumns = ['id', 'ticket_id', 'order_id', 'user_id', 'event_id', 'original_amount', 'refund_amount', 'refund_percentage', 'reason', 'explanation', 'refund_method', 'status', 'rejection_reason', 'approved_by', 'approved_at', 'processing_started_at', 'completed_at', 'payment_gateway_refund_id', 'payment_gateway_response', 'appeal_count', 'last_appeal_at', 'created_at', 'updated_at'];
foreach ($expectedColumns as $col) {
    $exists = in_array($col, $columnNames);
    echo sprintf("  %-30s %s\n", $col, $exists ? '✓' : '✗');
}

// Check indexes on refund_requests
echo "\nIndexes on refund_requests:\n";
$indexes = Capsule::connection()->select("PRAGMA index_list(refund_requests)");
$indexFound1 = false;
$indexFound2 = false;
foreach ($indexes as $index) {
    if ($index->name === 'idx_refund_user_status') $indexFound1 = true;
    if ($index->name === 'idx_refund_event_status') $indexFound2 = true;
}
echo $indexFound1 ? "  ✓ Index idx_refund_user_status exists\n" : "  ✗ Index idx_refund_user_status missing\n";
echo $indexFound2 ? "  ✓ Index idx_refund_event_status exists\n" : "  ✗ Index idx_refund_event_status missing\n";

// Verify refund_policies structure (Step 74)
echo "\nrefund_policies Structure:\n";
echo str_repeat("-", 50) . "\n";
$columns = $getColumns('refund_policies');
$columnNames = array_column($columns, 'name');
$expectedColumns = ['id', 'event_id', 'organizer_id', 'refund_window_days', 'refund_percentage_before_event', 'refund_percentage_after_event_start', 'allow_refunds_after_event_start', 'processing_time_business_days', 'allowed_refund_methods', 'requires_approval', 'auto_approve_threshold', 'max_refunds_per_user', 'refund_reasons', 'cancellation_policy', 'is_active', 'created_at', 'updated_at'];
foreach ($expectedColumns as $col) {
    $exists = in_array($col, $columnNames);
    echo sprintf("  %-30s %s\n", $col, $exists ? '✓' : '✗');
}

// Verify refund_appeals structure (Step 74)
echo "\nrefund_appeals Structure:\n";
echo str_repeat("-", 50) . "\n";
$columns = $getColumns('refund_appeals');
$columnNames = array_column($columns, 'name');
$expectedColumns = ['id', 'refund_request_id', 'user_id', 'appeal_reason', 'status', 'reviewed_by', 'review_notes', 'reviewed_at', 'created_at', 'updated_at'];
foreach ($expectedColumns as $col) {
    $exists = in_array($col, $columnNames);
    echo sprintf("  %-30s %s\n", $col, $exists ? '✓' : '✗');
}

// Verify settlement_policies structure (Step 75)
echo "\nsettlement_policies Structure:\n";
echo str_repeat("-", 50) . "\n";
$columns = $getColumns('settlement_policies');
$columnNames = array_column($columns, 'name');
$expectedColumns = ['id', 'organizer_tier', 'platform_commission_percentage', 'processing_fee_percentage', 'payout_frequency', 'minimum_payout_threshold', 'payout_hold_days', 'requires_approval', 'auto_approve_threshold', 'max_retries', 'retry_backoff_multiplier', 'tax_withholding_percentage', 'allowed_payout_methods', 'is_active', 'created_at', 'updated_at'];
foreach ($expectedColumns as $col) {
    $exists = in_array($col, $columnNames);
    echo sprintf("  %-30s %s\n", $col, $exists ? '✓' : '✗');
}

// Verify payouts structure (Step 75)
echo "\npayouts Structure:\n";
echo str_repeat("-", 50) . "\n";
$columns = $getColumns('payouts');
$columnNames = array_column($columns, 'name');
$expectedColumns = ['id', 'organizer_id', 'settlement_period_start_date', 'settlement_period_end_date', 'gross_revenue', 'refunds_deducted', 'net_revenue', 'platform_commission_percentage', 'platform_commission_amount', 'processing_fee_percentage', 'processing_fee_amount', 'tax_withholding_percentage', 'tax_withholding_amount', 'payout_amount', 'payout_method', 'payment_gateway_payout_id', 'payment_gateway_response', 'status', 'calculated_at', 'approved_by', 'approved_at', 'processing_started_at', 'completed_at', 'failure_reason', 'retry_count', 'next_retry_at', 'created_at', 'updated_at'];
foreach ($expectedColumns as $col) {
    $exists = in_array($col, $columnNames);
    echo sprintf("  %-30s %s\n", $col, $exists ? '✓' : '✗');
}

// Check indexes on payouts
echo "\nIndexes on payouts:\n";
$indexes = Capsule::connection()->select("PRAGMA index_list(payouts)");
$indexFound = false;
foreach ($indexes as $index) {
    if ($index->name === 'idx_payouts_organizer_status') {
        $indexFound = true;
        break;
    }
}
echo $indexFound ? "  ✓ Index idx_payouts_organizer_status exists\n" : "  ✗ Index idx_payouts_organizer_status missing\n";

// Verify payout_calculations structure (Step 75)
echo "\npayout_calculations Structure:\n";
echo str_repeat("-", 50) . "\n";
$columns = $getColumns('payout_calculations');
$columnNames = array_column($columns, 'name');
$expectedColumns = ['id', 'payout_id', 'organizer_id', 'settlement_period_start_date', 'settlement_period_end_date', 'event_ids', 'order_ids', 'refund_request_ids', 'total_order_count', 'total_tickets_sold', 'total_refunds_processed', 'calculation_details', 'calculated_at', 'calculated_by', 'created_at'];
foreach ($expectedColumns as $col) {
    $exists = in_array($col, $columnNames);
    echo sprintf("  %-30s %s\n", $col, $exists ? '✓' : '✗');
}

echo "\n=== Verification Complete ===\n";