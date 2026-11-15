<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = ['tickets', 'fraud_events', 'audit_logs', 'ticket_inventory', 'analytics_events_metrics'];

echo "=== TABLE EXISTENCE CHECK ===\n";
$allTables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
$existingTables = array_column($allTables, 'name');
foreach ($tables as $table) {
    $exists = in_array($table, $existingTables);
    echo str_pad($table, 30) . ($exists ? "EXISTS" : "MISSING") . "\n";
}

echo "\n=== TABLE STRUCTURE & INDEXES ===\n";
foreach ($tables as $table) {
    if (!in_array($table, $existingTables)) {
        continue;
    }
    echo "\n--- $table ---\n";
    
    // Columns
    $columns = DB::select("PRAGMA table_info('$table')");
    echo "Columns (" . count($columns) . "):\n";
    foreach ($columns as $col) {
        $nullStr = $col->notnull ? 'NOT NULL' : 'NULL';
        $defaultStr = $col->dflt_value !== null ? " DEFAULT " . $col->dflt_value : '';
        echo "  - $col->name: $col->type ($nullStr)$defaultStr\n";
    }
    
    // Indexes
    echo "Indexes:\n";
    $indexes = DB::select("PRAGMA index_list('$table')");
    foreach ($indexes as $idx) {
        $cols = DB::select("PRAGMA index_info('" . $idx->name . "')");
        $colList = implode(', ', array_column($cols, 'name'));
        $unique = $idx->unique ? 'UNIQUE' : 'INDEX';
        echo "  - $unique: {$idx->name} ($colList)\n";
    }
    
    // Foreign keys
    echo "Foreign Keys:\n";
    $fks = DB::select("PRAGMA foreign_key_list('$table')");
    if (empty($fks)) {
        echo "  (none)\n";
    }
    foreach ($fks as $fk) {
        echo "  - {$fk->from} -> {$fk->table}.{$fk->to} (ON DELETE: {$fk->on_delete})\n";
    }
}

echo "\n=== ROW COUNTS ===\n";
foreach ($tables as $table) {
    if (!in_array($table, $existingTables)) {
        continue;
    }
    $count = DB::table($table)->count();
    echo str_pad($table, 30) . $count . " rows\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";