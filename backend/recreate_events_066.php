<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$pdo = DB::connection()->getPdo();

echo "=== RECREATING EVENTS TABLE ===\n";

$exists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='events'")->fetch();
if ($exists) {
    echo "events table already exists - skipping\n";
} else {
    DB::statement("CREATE TABLE events (
        id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
        organizer_id INTEGER NOT NULL,
        user_id INTEGER,
        title VARCHAR NOT NULL,
        slug VARCHAR,
        description TEXT,
        start_datetime DATETIME,
        end_datetime DATETIME,
        timezone VARCHAR,
        currency VARCHAR NOT NULL DEFAULT 'NGN',
        venue_name VARCHAR,
        venue_address VARCHAR,
        latitude DECIMAL(10,7),
        longitude DECIMAL(10,7),
        banner_image_url VARCHAR,
        capacity INTEGER,
        max_tickets_per_order INTEGER,
        age_restriction VARCHAR,
        tags TEXT,
        status VARCHAR NOT NULL DEFAULT 'draft',
        cancellation_reason TEXT,
        created_at DATETIME,
        updated_at DATETIME,
        deleted_at DATETIME
    )");
    echo "events table created\n";

    // Recreate indexes
    DB::statement('CREATE INDEX IF NOT EXISTS events_organizer_id_index ON events(organizer_id)');
    DB::statement('CREATE INDEX IF NOT EXISTS events_user_id_index ON events(user_id)');
    DB::statement('CREATE INDEX IF NOT EXISTS events_status_index ON events(status)');
    DB::statement('CREATE INDEX IF NOT EXISTS events_organizer_id_status_index ON events(organizer_id, status)');
    DB::statement('CREATE INDEX IF NOT EXISTS events_deleted_at_index ON events(deleted_at)');
    DB::statement('CREATE INDEX IF NOT EXISTS events_start_datetime_index ON events(start_datetime)');
    echo "events indexes created\n";
}

echo "\n=== VERIFY EVENTS TABLE ===\n";
$cols = $pdo->query("PRAGMA table_info(events)")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo "  - " . $c['name'] . " (" . $c['type'] . ")\n";
}

echo "\n=== VERIFY FK references to events from checkout tables ===\n";
foreach (['orders', 'tickets', 'ticket_inventory', 'ticket_tiers'] as $t) {
    echo "  [$t]\n";
    foreach ($pdo->query("PRAGMA foreign_key_list($t)") as $fk) {
        echo "    " . $fk['from'] . " -> " . $fk['table'] . "." . $fk['to'] . " (onDelete=" . $fk['on_delete'] . ")\n";
    }
}

echo "\nDONE\n";
