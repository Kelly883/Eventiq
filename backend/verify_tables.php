<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pdo = DB::connection()->getPdo();
$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name IN ('orders', 'order_items', 'tickets', 'payments')");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Tables found: " . implode(', ', $tables) . PHP_EOL . PHP_EOL;

foreach (['orders', 'order_items', 'tickets', 'payments'] as $table) {
    echo "=== $table ===" . PHP_EOL;
    $cols = $pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo '  ' . $c['name'] . ' ' . $c['type'] . ($c['notnull'] ? ' NOT NULL' : '') . ($c['pk'] ? ' PK' : '') . PHP_EOL;
    }
    $indexes = $pdo->query('PRAGMA index_list(' . $table . ')')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($indexes as $idx) {
        $cols = $pdo->query('PRAGMA index_info(' . $idx['name'] . ')')->fetchAll(PDO::FETCH_ASSOC);
        $colNames = array_column($cols, 'name');
        echo '  INDEX ' . $idx['name'] . ' (' . implode(', ', $colNames) . ')' . PHP_EOL;
    }
    echo PHP_EOL;
}