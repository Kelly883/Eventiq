<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();

foreach (['users', 'roles', 'permission_user', 'permission_role', 'permission_requests'] as $t) {
    $exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$t'")->fetch();
    echo "=== $t ===\n";
    if (!$exists) {
        echo "  MISSING\n";
        continue;
    }
    echo "  Columns:\n";
    foreach ($pdo->query("PRAGMA table_info($t)") as $c) {
        echo "    - " . $c['name'] . " (" . $c['type'] . ") notnull=" . $c['notnull'] . " dflt=" . var_export($c['dflt_value'], true) . "\n";
    }
    echo "  Foreign keys:\n";
    foreach ($pdo->query("PRAGMA foreign_key_list($t)") as $fk) {
        echo "    - " . $fk['from'] . " -> " . $fk['table'] . "." . $fk['to'] . " (onDelete=" . $fk['on_delete'] . ")\n";
    }
    echo "\n";
}
