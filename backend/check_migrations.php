<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$db = Illuminate\Support\Facades\DB::connection();

echo "=== Recent migrations ===\n";
$migrations = $db->table('migrations')->orderBy('batch', 'desc')->limit(20)->get();
foreach ($migrations as $m) {
    echo "  {$m->batch}: {$m->migration}\n";
}
