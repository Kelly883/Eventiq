<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => __DIR__ . '/database/database.sqlite',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== REFUND TABLES DETAILED CHECK ===\n\n";

// Check PRAGMA for refund_requests to see column constraints
echo "1. REFUND_REQUESTS SCHEMA:\n";
echo str_repeat("-", 80) . "\n";
$columns = Capsule::connection()->select("PRAGMA table_info(refund_requests)");
foreach ($columns as $col) {
    echo sprintf("  %-30s type=%-12s nullable=%s default=%s pk=%s\n",
        $col->name, $col->type, $col->notnull ? 'NO' : 'YES',
        $col->dflt_value ?? 'NULL', $col->pk);
}

echo "\n2. REFUND_POLICIES SCHEMA:\n";
echo str_repeat("-", 80) . "\n";
$columns = Capsule::connection()->select("PRAGMA table_info(refund_policies)");
foreach ($columns as $col) {
    echo sprintf("  %-30s type=%-12s nullable=%s default=%s pk=%s\n",
        $col->name, $col->type, $col->notnull ? 'NO' : 'YES',
        $col->dflt_value ?? 'NULL', $col->pk);
}

echo "\n3. REFUND_APPEALS SCHEMA:\n";
echo str_repeat("-", 80) . "\n";
$columns = Capsule::connection()->select("PRAGMA table_info(refund_appeals)");
foreach ($columns as $col) {
    echo sprintf("  %-30s type=%-12s nullable=%s default=%s pk=%s\n",
        $col->name, $col->type, $col->notnull ? 'NO' : 'YES',
        $col->dflt_value ?? 'NULL', $col->pk);
}

echo "\n4. INDEXES:\n";
echo str_repeat("-", 80) . "\n";

foreach (['refund_requests', 'refund_policies', 'refund_appeals'] as $table) {
    echo "\n$table:\n";
    $indexes = Capsule::connection()->select("PRAGMA index_list($table)");
    foreach ($indexes as $idx) {
        $info = Capsule::connection()->select("PRAGMA index_info($idx->name)");
        $cols = array_column($info, 'name');
        echo sprintf("  %-35s %s\n", $idx->name, implode(', ', $cols));
    }
}

echo "\n5. FOREIGN KEYS:\n";
echo str_repeat("-", 80) . "\n";

foreach (['refund_requests', 'refund_policies', 'refund_appeals'] as $table) {
    echo "\n$table:\n";
    $fks = Capsule::connection()->select("PRAGMA foreign_key_list($table)");
    foreach ($fks as $fk) {
        echo sprintf("  %-20s -> %s(%s) on_delete=%s\n", $fk->from, $fk->table, $fk->to, $fk->on_delete);
    }
}

echo "\n6. COUNT CHECKS:\n";
echo str_repeat("-", 80) . "\n";
echo sprintf("  %-30s %d\n", 'refund_requests', Capsule::connection()->table('refund_requests')->count());
echo sprintf("  %-30s %d\n", 'refund_policies', Capsule::connection()->table('refund_policies')->count());
echo sprintf("  %-30s %d\n", 'refund_appeals', Capsule::connection()->table('refund_appeals')->count());

echo "\n=== CHECK COMPLETE ===\n";