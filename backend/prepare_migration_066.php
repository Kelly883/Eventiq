<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();

echo "STEP1: Dropping ALL views\n";
$views = $pdo->query("SELECT name FROM sqlite_master WHERE type='view'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($views as $v) {
    try {
        DB::statement('DROP VIEW IF EXISTS "' . $v['name'] . '"');
        echo "DROPPED VIEW: " . $v['name'] . "\n";
    } catch (\Throwable $e) {
        echo "FAILED view " . $v['name'] . ": " . $e->getMessage() . "\n";
    }
}

echo "STEP2: Dropping temp tables\n";
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($tables as $t) {
    if (strpos($t['name'], '__temp__') === 0) {
        try {
            DB::statement('DROP TABLE IF EXISTS "' . $t['name'] . '"');
            echo "DROPPED TEMP: " . $t['name'] . "\n";
        } catch (\Throwable $e) {
            echo "FAILED temp " . $t['name'] . ": " . $e->getMessage() . "\n";
        }
    }
}

echo "STEP3: Remaining views\n";
$views = $pdo->query("SELECT name FROM sqlite_master WHERE type='view'")->fetchAll(PDO::FETCH_ASSOC);
echo (count($views) === 0) ? "(none)\n" : implode(", ", array_column($views, 'name')) . "\n";

echo "STEP4: Remaining temp tables\n";
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_ASSOC);
$temps = array_filter($tables, fn($t) => strpos($t['name'], '__temp__') === 0);
echo (count($temps) === 0) ? "(none)\n" : implode(", ", array_column($temps, 'name')) . "\n";

echo "DONE\n";
