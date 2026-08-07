<?php

define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$default = config('database.default');
echo "DB_CONNECTION=$default" . PHP_EOL;
$cfg = config("database.connections.{$default}");
echo "CONFIG=" . json_encode($cfg, JSON_PRETTY_PRINT) . PHP_EOL;

$c = Illuminate\Support\Facades\DB::connection();
echo "Driver=" . $c->getDriverName() . PHP_EOL;
if (method_exists($c, 'getDatabaseName')) {
    echo "getDatabaseName()=" . $c->getDatabaseName() . PHP_EOL;
}
if ($c->getDriverName() === 'sqlite') {
    try {
        $r = $c->select("SELECT file FROM pragma_database_list WHERE name='main'");
        if (isset($r[0]->file)) {
            echo "SQLite ACTUAL FILE = " . $r[0]->file . PHP_EOL;
            echo "EXISTS = " . (file_exists($r[0]->file) ? "YES size=" . filesize($r[0]->file) : "NO") . PHP_EOL;
        }
    } catch (\Throwable) { }
}

echo PHP_EOL . "Actual tables:" . PHP_EOL;
if ($c->getDriverName() === 'sqlite') {
    $rows = $c->select("SELECT name, type FROM sqlite_master WHERE type IN ('table','view') ORDER BY name");
    foreach ($rows as $r) {
        echo "  - [{$r->type}] {$r->name}" . PHP_EOL;
    }
}
echo PHP_EOL . "Migration table status:" . PHP_EOL;
$migrationRows = $c->table('migrations')->orderBy('migration')->get();
foreach ($migrationRows as $m) {
    if (stripos($m->migration, 'step66') !== false || stripos($m->migration, '066') !== false || stripos($m->migration, 'settlement') !== false || stripos($m->migration, 'optimize_calendar') !== false || stripos($m->migration, 'calendar_availability_view') !== false) {
        echo "  batch {$m->batch}: {$m->migration}" . PHP_EOL;
    }
}

echo PHP_EOL . "=== Columns introspection for 4 checkout tables ===" . PHP_EOL;
foreach (['orders','order_items','tickets','payments'] as $tbl) {
    $cols = $c->select("PRAGMA table_info({$tbl})");
    echo "[$tbl] " . count($cols) . " cols: ";
    foreach ($cols as $c) { echo $c->name . ","; }
    echo PHP_EOL;
    $idx = $c->select("PRAGMA index_list({$tbl})");
    echo "  Indexes: ";
    foreach ($idx as $i) { echo "{$i->name}(unique={$i->unique}), "; }
    echo PHP_EOL;
    $fks = $c->select("PRAGMA foreign_key_list({$tbl})");
    echo "  FKs: ";
    foreach ($fks as $f) { echo "{$f->from}->{$f->table}.{$f->to} ON DELETE {$f->on_delete}, "; }
    echo PHP_EOL;
}
