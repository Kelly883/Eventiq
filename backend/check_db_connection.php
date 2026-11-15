<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Default connection: " . config('database.default') . "\n";
echo "DB_CONNECTION env: " . env('DB_CONNECTION') . "\n";
echo "DB_DATABASE env: " . env('DB_DATABASE') . "\n";

$conn = DB::connection();
echo "Connection name: " . $conn->getName() . "\n";
echo "Database name: " . $conn->getDatabaseName() . "\n";

// List all tables
$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
echo "\nAll tables in database:\n";
foreach ($tables as $t) {
    echo "  - " . $t->name . "\n";
}