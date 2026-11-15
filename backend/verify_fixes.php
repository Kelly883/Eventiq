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

echo "\n=== tickets columns ===\n";
$columns = $db->select("PRAGMA table_info('tickets')");
foreach ($columns as $col) {
    echo "  {$col->name} ({$col->type})\n";
}

echo "\n=== orders columns ===\n";
$columns = $db->select("PRAGMA table_info('orders')");
foreach ($columns as $col) {
    echo "  {$col->name} ({$col->type})\n";
}

echo "\n=== fraud_events indexes ===\n";
$indexes = $db->select("PRAGMA index_list('fraud_events')");
foreach ($indexes as $idx) {
    echo "  {$idx->name} (unique: " . ($idx->unique ? 'yes' : 'no') . ")\n";
}

echo "\n=== Triggers ===\n";
$triggers = $db->select("SELECT name, sql FROM sqlite_master WHERE type='trigger'");
foreach ($triggers as $trigger) {
    echo "Trigger: {$trigger->name}\n";
}

echo "\n=== Duplicate index check ===\n";
$tables = ['tickets', 'payments', 'orders', 'order_items'];
foreach ($tables as $table) {
    $indexes = $db->select("PRAGMA index_list('$table')");
    $names = array_column($indexes, 'name');
    $dupes = array_unique(array_diff_assoc($names, array_unique($names)));
    if ($dupes) {
        echo "$table has duplicate indexes: " . implode(', ', $dupes) . "\n";
    } else {
        echo "$table: no duplicate index names\n";
    }
}
