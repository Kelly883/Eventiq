<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();

echo "=== Migrations table (last 10 ran) ===\n";
$rows = $pdo->query("SELECT migration FROM migrations")->fetchAll(PDO::FETCH_ASSOC);
$all = array_column($rows, 'migration');
echo "Total migrations recorded: " . count($all) . "\n";
echo "Last 5:\n";
foreach (array_slice($all, -5) as $m) {
    echo "  - " . $m . "\n";
}

echo "\n=== Permissions table exists? ===\n";
$t = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='permissions'")->fetch();
echo "permissions exists: " . ($t ? "YES" : "NO") . "\n";

if ($t) {
    echo "=== Permissions columns ===\n";
    $cols = $pdo->query("PRAGMA table_info(permissions)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  - " . $c['name'] . " (" . $c['type'] . ")\n";
    }
    echo "=== Permissions indexes ===\n";
    $idx = $pdo->query("PRAGMA index_list(permissions)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($idx as $i) {
        echo "  - " . $i['name'] . " (unique=" . $i['unique'] . ")\n";
    }
}

echo "\n=== Roles table exists? ===\n";
$t = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='roles'")->fetch();
echo "roles exists: " . ($t ? "YES" : "NO") . "\n";
if ($t) {
    $cols = $pdo->query("PRAGMA table_info(roles)")->fetchAll(PDO::FETCH_ASSOC);
    echo "roles columns:\n";
    foreach ($cols as $c) {
        echo "  - " . $c['name'] . " (" . $c['type'] . ")\n";
    }
}
