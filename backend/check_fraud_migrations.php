<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fraud-related migrations ===\n";
$migrations = DB::select("SELECT migration, batch FROM migrations WHERE migration LIKE '%fraud%' ORDER BY batch");
foreach ($migrations as $m) {
    echo "Batch {$m->batch}: {$m->migration}\n";
}

echo "\n=== Checking if fraud_events table exists ===\n";
$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='fraud_events'");
if (empty($tables)) {
    echo "TABLE NOT FOUND\n";
    exit(1);
}
echo "Table exists.\n";

echo "\n=== Table Structure ===\n";
$columns = DB::select("PRAGMA table_info(fraud_events)");
foreach ($columns as $col) {
    printf("%-30s %s\n", $col->name, $col->type);
}

echo "\n=== Indexes ===\n";
$indexes = DB::select("PRAGMA index_list(fraud_events)");
foreach ($indexes as $idx) {
    echo "Index: {$idx->name} (unique: " . ($idx->unique ? 'yes' : 'no') . ")\n";
    $cols = DB::select("PRAGMA index_info('{$idx->name}')");
    $colNames = array_map(fn($c) => $c->name, $cols);
    echo "  Columns: " . implode(', ', $colNames) . "\n";
}

echo "\n=== Foreign Keys ===\n";
$fks = DB::select("PRAGMA foreign_key_list(fraud_events)");
foreach ($fks as $fk) {
    echo "FK: {$fk->from} -> {$fk->table}.{$fk->to}\n";
}