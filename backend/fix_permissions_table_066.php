<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();

// Disable FK temporarily to allow recreation
DB::statement('PRAGMA foreign_keys = OFF');

echo "=== Recreating permissions table (base schema) ===\n";

$exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='permissions'")->fetch();
if ($exists) {
    echo "permissions table already exists - dropping and recreating cleanly\n";
    DB::statement('DROP TABLE permissions');
}

// Recreate the permissions table matching the ORIGINAL create_permissions_table migration.
// Column is 'group' (not group_name).
DB::statement("CREATE TABLE permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    name VARCHAR NOT NULL,
description VARCHAR,
    `group` VARCHAR,
    created_at DATETIME,
    updated_at DATETIME,
    CONSTRAINT permissions_name_unique UNIQUE (name)
)");
echo "permissions table created\n";

// Re-enable FK
DB::statement('PRAGMA foreign_keys = ON');

echo "\n=== Verify permissions table ===\n";
$cols = $pdo->query("PRAGMA table_info(permissions)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  - " . $c['name'] . " (" . $c['type'] . ")\n";
}

echo "\n=== Verify indices ===\n";
foreach ($pdo->query("PRAGMA index_list(permissions)") as $i) {
    echo "  - " . $i['name'] . " (unique=" . $i['unique'] . ")\n";
}

echo "\n=== Verify FK from pivot tables to permissions ===\n";
foreach (['permission_user', 'permission_role'] as $t) {
    echo "  [$t]\n";
    foreach ($pdo->query("PRAGMA foreign_key_list($t)") as $fk) {
        echo "    " . $fk['from'] . " -> " . $fk['table'] . "." . $fk['to'] . "\n";
    }
}

echo "\nDONE\n";
