<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();

echo "=== MIGRATIONS in order ran (all) ===\n";
$rows = $pdo->query("SELECT migration FROM migrations ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$all = array_column($rows, 'migration');
foreach ($all as $m) {
    echo "  - " . $m . "\n";
}

echo "\n=== DELIVERY_EVENTS TABLE ===\n";
$t = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='delivery_events'")->fetch();
echo "exists: " . ($t ? "YES" : "NO") . "\n";
if ($t) {
    echo "columns:\n";
    foreach ($pdo->query("PRAGMA table_info(delivery_events)") as $c) {
        echo "  - " . $c['name'] . " (" . $c['type'] . ")\n";
    }
    echo "indexes:\n";
    foreach ($pdo->query("PRAGMA index_list(delivery_events)") as $i) {
        echo "  - " . $i['name'] . " (unique=" . $i['unique'] . ")\n";
    }
}

echo "\n=== DELIVERY_PREFERENCES TABLE ===\n";
$t = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='delivery_preferences'")->fetch();
echo "exists: " . ($t ? "YES" : "NO") . "\n";
if ($t) {
    echo "columns:\n";
    foreach ($pdo->query("PRAGMA table_info(delivery_preferences)") as $c) {
        echo "  - " . $c['name'] . " (" . $c['type'] . ")\n";
    }
    echo "indexes:\n";
    foreach ($pdo->query("PRAGMA index_list(delivery_preferences)") as $i) {
        echo "  - " . $i['name'] . " (unique=" . $i['unique'] . ")\n";
    }
}

echo "\n=== FRAUD_EVENTS TABLE ===\n";
$t = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='fraud_events'")->fetch();
echo "exists: " . ($t ? "YES" : "NO") . "\n";
if ($t) {
    echo "columns:\n";
    foreach ($pdo->query("PRAGMA table_info(fraud_events)") as $c) {
        echo "  - " . $c['name'] . " (" . $c['type'] . ")\n";
    }
    echo "indexes:\n";
    foreach ($pdo->query("PRAGMA index_list(fraud_events)") as $i) {
        echo "  - " . $i['name'] . " (unique=" . $i['unique'] . ")\n";
    }
}

echo "\n=== EVENTS CALENDAR SUMMARY TABLE ===\n";
$t = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='events_calendar_summary'")->fetch();
echo "exists: " . ($t ? "YES" : "NO") . "\n";

echo "\n=== ALL VIEWS ===\n";
foreach ($pdo->query("SELECT name FROM sqlite_master WHERE type='view'") as $v) {
    echo "  - " . $v['name'] . "\n";
}

echo "\n=== EVENTS CATEGORY COLUMN? ===\n";
$has = $pdo->query("PRAGMA table_info(events)");
$cats = [];
foreach ($has as $c) {
    if (in_array($c['name'], ['category', 'category_id', 'location_id'])) {
        $cats[] = $c['name'];
    }
}
echo "category columns: " . (empty($cats) ? "NONE" : implode(', ', $cats)) . "\n";

echo "\n=== TICKET_INVENTORY COLUMNS ===\n";
$t = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='ticket_inventory'")->fetch();
if ($t) {
    foreach ($pdo->query("PRAGMA table_info(ticket_inventory)") as $c) {
        echo "  - " . $c['name'] . " (" . $c['type'] . ")\n";
    }
} else {
    echo "  MISSING\n";
}

echo "\n=== PRICING_WINDOWS COLUMNS ===\n";
$t = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='pricing_windows'")->fetch();
if ($t) {
    foreach ($pdo->query("PRAGMA table_info(pricing_windows)") as $c) {
        echo "  - " . $c['name'] . " (" . $c['type'] . ")\n";
    }
} else {
    echo "  MISSING\n";
}

echo "\nDONE\n";
