<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();

echo "=== DROPPING CORRUPT VIEWS ===\n";

$viewsToDrop = [
    'calendar_event_availability_view',
    'calendar_date_availability_summary_view',
];

foreach ($viewsToDrop as $view) {
    try {
        DB::statement("DROP VIEW IF EXISTS \"" . $view . "\"");
        echo "DROPPED: " . $view . "\n";
    } catch (\Throwable $e) {
        echo "FAILED to drop " . $view . ": " . $e->getMessage() . "\n";
    }
}

echo "\n=== REMAINING VIEWS ===\n";
$views = $pdo->query("SELECT name FROM sqlite_master WHERE type='view'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($views as $v) {
    echo "  - " . $v['name'] . "\n";
}

echo "\nDONE\n";
