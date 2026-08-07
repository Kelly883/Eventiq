<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();

echo "=== RECREATING EVENTS TABLE (ORIGINAL BASE SCHEMA) ===\n";

DB::statement('PRAGMA foreign_keys = OFF');

$exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='events'")->fetch();
if ($exists) {
    echo "Dropping existing events table\n";
    DB::statement('DROP TABLE events');
}

// Recreate with the ORIGINAL base schema from 2026_07_04_000400_create_events_table
DB::statement("CREATE TABLE events (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    organizer_id INTEGER NOT NULL,
    title VARCHAR NOT NULL,
    description TEXT,
    start_date DATETIME,
    end_date DATETIME,
    location VARCHAR,
    banner_path VARCHAR,
    capacity INTEGER,
    status VARCHAR NOT NULL DEFAULT 'draft',
    created_at DATETIME,
    updated_at DATETIME
)");
echo "events table created with base schema\n";

DB::statement('PRAGMA foreign_keys = ON');

echo "\n=== VERIFY EVENTS TABLE ===\n";
$cols = $pdo->query("PRAGMA table_info(events)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  - " . $c['name'] . " (" . $c['type'] . ")\n";
}

echo "\nDONE\n";
