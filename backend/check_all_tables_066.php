<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();

// Get all actual tables
$actual = [];
$rows = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $actual[] = $r['name'];
}

echo "=== ACTUAL TABLES IN DB (" . count($actual) . ") ===\n";
sort($actual);
echo implode(", ", $actual) . "\n";

// Get migrations recorded as ran
$migs = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_ASSOC);
$migNames = array_column($migs, 'migration');

// Extract table names from migration files to cross-reference
echo "\n=== CORE CHECKOUT TABLES ===\n";
$core = ['orders', 'order_items', 'tickets', 'payments'];
foreach ($core as $t) {
    echo "[$t] " . (in_array($t, $actual) ? "EXISTS" : "MISSING") . "\n";
}

echo "\n=== Key referenced tables ===\n";
foreach (['users', 'events', 'ticket_tiers', 'roles', 'permissions'] as $t) {
    echo "[$t] " . (in_array($t, $actual) ? "EXISTS" : "MISSING") . "\n";
}
