<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();

function tableExists($pdo, $name) {
    return $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name=" . $pdo->quote($name))->fetch() !== false;
}

$log = [];

$tablesToCheck = [
    'delivery_preferences',
    'user_dashboard_preferences',
    'delivery_events',
    'fraud_events',
    'events_calendar_summary',
    'permissions',
];

$log[] = "=== TABLE EXISTENCE ===";
foreach ($tablesToCheck as $t) {
    $log[] = "[" . $t . "] " . (tableExists($pdo, $t) ? "EXISTS" : "MISSING");
}

$log[] = "\n=== ALL VIEWS ===";
$vrows = $pdo->query("SELECT name FROM sqlite_master WHERE type='view'")->fetchAll(PDO::FETCH_ASSOC);
$vnames = array_column($vrows, 'name');
$log[] = (count($vnames) === 0 ? "(none)" : implode(", ", $vnames));

$log[] = "\n=== MIGRATIONS (last 20) ===";
$mrows = $pdo->query("SELECT migration FROM migrations ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
foreach ($mrows as $m) {
    $log[] = "  - " . $m['migration'];
}

file_put_contents(__DIR__ . '/diag_out_066.txt', implode("\n", $log) . "\n");
echo "WROTE diag_out_066.txt\n";
