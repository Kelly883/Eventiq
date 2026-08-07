<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();

$log = [];

function tableExists($pdo, $name) {
    return $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name=" . $pdo->quote($name))->fetch() !== false;
}

$tables = ['users', 'orders', 'tickets', 'events', 'organizers', 'ticket_tiers', 'payments', 'delivery_events'];

foreach ($tables as $t) {
    $log[] = "=== $t ===";
    if (!tableExists($pdo, $t)) {
        $log[] = "  MISSING";
        continue;
    }
    foreach ($pdo->query("PRAGMA table_info(" . $t . ")") as $c) {
        $log[] = "  - " . $c['name'] . " (" . $c['type'] . ") pk=" . $c['pk'] . " notnull=" . $c['notnull'] . " dflt=" . var_export($c['dflt_value'], true);
    }
    $log[] = "";
}

file_put_contents(__DIR__ . '/ids_out_066.txt', implode("\n", $log) . "\n");
echo "WROTE ids_out_066.txt\n";
