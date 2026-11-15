<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$driver = 'sqlite';
$database = 'c:\Users\PC\Downloads\EventIQ\backend\database\test_temp5.sqlite';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($driver . ':dbname=' . $database . ';', '', '', $options);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Get all tables
    $tables = $pdo->query('SELECT name FROM sqlite_master WHERE type="table" ORDER BY name')->fetchAll();
    echo "Tables in test_temp5.sqlite:\n";
    foreach ($tables as $t) {
        echo "  - " . $t['name'] . "\n";
    }
    
    // Check for check_ins specifically
    foreach ($tables as $t) {
        if ($t['name'] === 'check_ins') {
            echo "\nFound check_ins table!\n";
            $columns = $pdo->query('PRAGMA table_info(check_ins)')->fetchAll();
            echo "Columns:\n";
            foreach ($columns as $c) {
                echo "  - " . $c['name'] . " (" . $c['type'] . ") " . ($c['notnull'] ? "NOT NULL" : "NULL") . "\n";
            }
            $indexes = $pdo->query('PRAGMA index_list(check_ins)')->fetchAll();
            echo "Indexes:\n";
            foreach ($indexes as $idx) {
                $cols = $pdo->query('PRAGMA index_info("' . $idx['name'] . '")')->fetchAll();
                $colList = implode(', ', array_column($cols, 'name'));
                echo "  - " . ($idx['unique'] ? 'UNIQUE' : 'INDEX') . ": " . $idx['name'] . " (" . $colList . ")\n";
            }
            $fks = $pdo->query('PRAGMA foreign_key_list(check_ins)')->fetchAll();
            echo "Foreign Keys:\n";
            foreach ($fks as $fk) {
                echo "  - " . $fk['from'] . " -> " . $fk['table'] . "." . $fk['to'] . " ON DELETE: " . $fk['on_delete'] . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}