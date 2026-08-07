<?php

define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$c = Illuminate\Support\Facades\DB::connection();

$suspectTables = ['__temp__orders','__temp__order_items','__temp__tickets','__temp__payments','orders','order_items','tickets','payments'];
foreach ($suspectTables as $t) {
    $has = Illuminate\Support\Facades\Schema::hasTable($t);
    echo "Schema::hasTable('$t') = " . ($has ? 'TRUE' : 'FALSE') . PHP_EOL;
    if ($has) {
        echo "  cols=" . count($c->select("PRAGMA table_info($t)")) . PHP_EOL;
    }
}

echo PHP_EOL . "Now let's do rename __temp__* -> real tables:" . PHP_EOL;
$renames = [
    '__temp__orders' => 'orders',
    '__temp__order_items' => 'order_items',
    '__temp__tickets' => 'tickets',
    '__temp__payments' => 'payments',
];
foreach ($renames as $old => $new) {
    if (Illuminate\Support\Facades\Schema::hasTable($old) && !Illuminate\Support\Facades\Schema::hasTable($new)) {
        try {
            Illuminate\Support\Facades\DB::statement("ALTER TABLE \"$old\" RENAME TO \"$new\"");
            echo "  RENAMED $old -> $new  ✅" . PHP_EOL;
        } catch (\Throwable $e) {
            echo "  FAIL RENAME $old -> $new : " . $e::class . " " . $e->getMessage() . PHP_EOL;
        }
    } else {
        echo "  SKIP: hasTable($old)=" . (int)Illuminate\Support\Facades\Schema::hasTable($old) . " hasTable($new)=" . (int)Illuminate\Support\Facades\Schema::hasTable($new) . PHP_EOL;
    }
}

echo PHP_EOL . "After renames:" . PHP_EOL;
foreach (['orders','order_items','tickets','payments'] as $t) {
    $cols = $c->select("PRAGMA table_info($t)");
    $realNames = array_column($cols, 'name');
    echo "[$t] " . count($realNames) . " cols: " . implode(',', $realNames) . PHP_EOL;
}
