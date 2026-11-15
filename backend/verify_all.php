<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$db = Illuminate\Support\Facades\DB::connection();

echo "=== check_ins columns ===\n";
$columns = $db->select("PRAGMA table_info('check_ins')");
foreach ($columns as $col) {
    echo "  {$col->name} ({$col->type})\n";
}

echo "\n=== check_ins indexes ===\n";
$indexes = $db->select("PRAGMA index_list('check_ins')");
foreach ($indexes as $idx) {
    $unique = $idx->unique ? 'yes' : 'no';
    echo "  {$idx->name} (unique: $unique)\n";
}

echo "\n=== check_ins has deleted_at? ===\n";
$hasDeletedAt = false;
foreach ($columns as $col) {
    if ($col->name === 'deleted_at') {
        $hasDeletedAt = true;
        break;
    }
}
echo $hasDeletedAt ? 'YES' : 'NO';

echo "\n=== tickets columns (new ones) ===\n";
$columns = $db->select("PRAGMA table_info('tickets')");
$newCols = ['seat_number', 'section', 'row', 'price_paid', 'currency'];
$colNames = array_column($columns, 'name');
foreach ($newCols as $col) {
    echo "  $col: " . (in_array($col, $colNames) ? 'EXISTS' : 'MISSING') . "\n";
}

echo "\n=== orders columns (new ones) ===\n";
$columns = $db->select("PRAGMA table_info('orders')");
$colNames = array_column($columns, 'name');
$newCols = ['completed_at', 'refunded_at'];
foreach ($newCols as $col) {
    echo "  $col: " . (in_array($col, $colNames) ? 'EXISTS' : 'MISSING') . "\n";
}

echo "\n=== fraud_events indexes ===\n";
$indexes = $db->select('PRAGMA index_list(fraud_events)');
foreach ($indexes as $idx) {
    $unique = $idx->unique ? 'yes' : 'no';
    echo "  {$idx->name} (unique: $unique)\n";
}

echo "\n=== Triggers ===\n";
$triggers = $db->select("SELECT name, sql FROM sqlite_master WHERE type='trigger'");
foreach ($triggers as $trigger) {
    echo "Trigger: {$trigger->name}\n";
}

echo "\n=== payments indexes ===\n";
$indexes = $db->select("PRAGMA index_list('payments')");
foreach ($indexes as $idx) {
    $unique = $idx->unique ? 'yes' : 'no';
    echo "  {$idx->name} (unique: $unique)\n";
}
