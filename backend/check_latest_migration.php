<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$db = Illuminate\Support\Facades\DB::connection();

$latest = $db->table('migrations')->orderBy('id', 'desc')->first();
echo "Latest migration: {$latest->migration}\n";

echo "\nAll migrations (last 30):\n";
$migrations = $db->table('migrations')->orderBy('id', 'desc')->limit(30)->get();
foreach ($migrations as $m) {
    echo "  {$m->id}: {$m->migration}\n";
}
