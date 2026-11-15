<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== CHECK-IN SYSTEM DATABASE VERIFICATION ===\n\n";
echo "Database: " . DB::connection()->getDatabaseName() . "\n";
echo "Driver: " . DB::getDriverName() . "\n\n";

// The five tables from the PRD
$tables = [
    'tickets',
    'fraud_events',
    'audit_logs',
    'ticket_inventory',
    'analytics_events_metrics',
];

echo "=== 1. TABLE EXISTENCE ===\n";
foreach ($tables as $t) {
    $exists = Schema::hasTable($t);
    echo str_pad($t, 30) . ': ' . ($exists ? 'EXISTS' : 'MISSING') . "\n";
}

echo "\n=== 2. COLUMNS PER TABLE ===\n";
foreach ($tables as $t) {
    if (!Schema::hasTable($t)) {
        echo "\n--- $t: TABLE MISSING ---\n";
        continue;
    }
    echo "\n--- $t ---\n";
    $cols = Schema::getColumnListing($t);
    foreach ($cols as $col) {
        $type = Schema::getColumnType($t, $col);
        echo "  $col ($type)\n";
    }
}

echo "\n=== 3. INDEXES PER TABLE ===\n";
foreach ($tables as $t) {
    if (!Schema::hasTable($t)) {
        continue;
    }
    echo "\n--- $t ---\n";
    $indexes = DB::select("PRAGMA index_list('$t')");
    foreach ($indexes as $idx) {
        $cols = DB::select("PRAGMA index_info('" . $idx->name . "')");
        $colList = implode(', ', array_column($cols, 'name'));
        echo "  {$idx->name} [" . ($idx->unique ? 'UNIQUE' : 'INDEX') . "] ($colList)\n";
    }
}

echo "\n=== 4. FOREIGN KEYS PER TABLE ===\n";
foreach ($tables as $t) {
    if (!Schema::hasTable($t)) {
        continue;
    }
    echo "\n--- $t ---\n";
    $fks = DB::select("PRAGMA foreign_key_list('$t')");
    if (empty($fks)) {
        echo "  (no foreign keys)\n";
    }
    foreach ($fks as $fk) {
        echo "  {$fk->from} -> {$fk->table}.{$fk->to} (on delete: {$fk->on_delete})\n";
    }
}

echo "\n=== 5. ROW COUNTS ===\n";
foreach ($tables as $t) {
    if (!Schema::hasTable($t)) {
        continue;
    }
    $count = DB::table($t)->count();
    echo str_pad($t, 30) . ': ' . $count . " rows\n";
}

echo "\n=== 6. MIGRATION STATUS ===\n";
$migrations = DB::table('migrations')->orderBy('id')->get();
echo "Total migrations run: " . $migrations->count() . "\n";
$last = $migrations->last();
if ($last) {
    echo "Last migration: {$last->migration} (batch {$last->batch})\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";