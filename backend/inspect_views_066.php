<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();

echo "=== ALL VIEWS ===\n";
$views = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='view'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($views as $v) {
    echo "VIEW: " . $v['name'] . "\n";
    echo $v['sql'] . "\n\n";
}

echo "=== EVENTS TABLE COLUMNS ===\n";
foreach ($pdo->query("PRAGMA table_info(events)") as $c) {
    echo "  - " . $c['name'] . " (" . $c['type'] . ")\n";
}
