<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$pdo = DB::connection()->getPdo();

echo "=== ALL TABLES WITH __temp__ in name ===\n";
$rows = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    if (strpos($r['name'], '__temp__') === 0) {
        echo "TEMP TABLE: " . $r['name'] . "\n";
    }
}

echo "\n=== Dropping __temp__ tables ===\n";
foreach ($rows as $r) {
    if (strpos($r['name'], '__temp__') === 0) {
        try {
            DB::statement("DROP TABLE IF EXISTS \"" . $r['name'] . "\"");
            echo "DROPPED: " . $r['name'] . "\n";
        } catch (\Throwable $e) {
            echo "FAILED to drop " . $r['name'] . ": " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== Remaining __temp__ tables ===\n";
$rows = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_ASSOC);
$found = false;
foreach ($rows as $r) {
    if (strpos($r['name'], '__temp__') === 0) {
        echo "TEMP TABLE: " . $r['name'] . "\n";
        $found = true;
    }
}
if (!$found) {
    echo "(none)\n";
}

echo "\n=== Check permissions table state ===\n";
$cols = $pdo->query("PRAGMA table_info(permissions)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['name'] . " (" . $c['type'] . ")\n";
}
