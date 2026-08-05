<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        DB::statement('DROP VIEW IF EXISTS calendar_events_availability');

        $eventDateExpression = Schema::hasColumn('events', 'start_datetime')
            ? 'DATE(e.start_datetime)'
            : 'DATE(e.start_date)';

        $categoryExpression = Schema::hasColumn('events', 'category_id')
            ? 'e.category_id'
            : 'NULL';

        $locationExpression = Schema::hasColumn('events', 'location_id')
            ? 'e.location_id'
            : 'NULL';

        DB::statement("
            CREATE VIEW calendar_events_availability AS
            SELECT
                e.id AS event_id,
                e.status,
                {$eventDateExpression} AS event_date,
                {$categoryExpression} AS category_id,
                {$locationExpression} AS location_id,
                COALESCE(SUM(ti.total_quantity), 0) AS total_tickets,
                COALESCE(SUM(ti.sold_quantity), 0) AS sold_tickets,
                COALESCE(SUM(ti.reserved_quantity), 0) AS reserved_tickets,
                COALESCE(SUM(ti.total_quantity - ti.sold_quantity - ti.reserved_quantity), 0) AS remaining_tickets,
                CASE
                    WHEN COALESCE(SUM(ti.total_quantity), 0) = 0 THEN 'unavailable'
                    WHEN COALESCE(SUM(ti.total_quantity - ti.sold_quantity - ti.reserved_quantity), 0) = 0 THEN 'sold_out'
                    WHEN COALESCE(SUM(ti.total_quantity - ti.sold_quantity - ti.reserved_quantity), 0) < 10 THEN 'low_stock'
                    ELSE 'available'
                END AS availability_status
            FROM events e
            LEFT JOIN ticket_inventory ti ON e.id = ti.event_id
            GROUP BY
                e.id,
                e.status,
                {$eventDateExpression},
                {$categoryExpression},
                {$locationExpression}
        ");
    }

    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS calendar_events_availability');
    }
};
