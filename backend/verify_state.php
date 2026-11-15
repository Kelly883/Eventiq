<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$db = Illuminate\Support\Facades\DB::connection();

$tables = $db->select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
echo "=== Tables ===\n";
foreach ($tables as $t) {
    echo "  {$t->name}\n";
}

echo "\n=== fraud_events indexes ===\n";
$indexes = $db->select('PRAGMA index_list(fraud_events)');
foreach ($indexes as $idx) {
    $unique = $idx->unique ? 'yes' : 'no';
    echo "  {$idx->name} (unique: $unique)\n";
}

echo "\n=== fraud_events row count ===\n";
echo $db->table('fraud_events')->count() . "\n";
