<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS events_by_date');

        if (!Schema::hasTable('events') || !Schema::hasColumn('events', 'start_datetime')) {
            return;
        }

        DB::statement('
            CREATE VIEW events_by_date AS
            SELECT
                DATE(start_datetime) AS event_date,
                COUNT(*) AS total_events,
                SUM(capacity) AS total_capacity,
                SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) AS published_events,
                SUM(CASE WHEN status = "published" THEN capacity ELSE 0 END) AS published_capacity
            FROM events
            WHERE start_datetime IS NOT NULL
            GROUP BY DATE(start_datetime)
            ORDER BY event_date
        ');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS events_by_date');

        if (!Schema::hasTable('events')) {
            return;
        }

        DB::statement('
            CREATE VIEW events_by_date AS
            SELECT
                DATE(start_date) AS event_date,
                COUNT(*) AS total_events,
                SUM(capacity) AS total_capacity,
                SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) AS published_events,
                SUM(CASE WHEN status = "published" THEN capacity ELSE 0 END) AS published_capacity
            FROM events
            WHERE start_datetime IS NOT NULL
            GROUP BY DATE(start_datetime)
            ORDER BY event_date
        ');
    }
};
