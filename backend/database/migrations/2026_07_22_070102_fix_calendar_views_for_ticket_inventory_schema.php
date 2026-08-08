<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS calendar_events_availability');
        DB::statement('DROP VIEW IF EXISTS calendar_event_availability_view');
        DB::statement('DROP VIEW IF EXISTS calendar_date_availability_summary_view');

        DB::statement("
            CREATE VIEW calendar_events_availability AS
            SELECT
                e.id AS event_id,
                e.status,
                DATE(e.start_datetime) AS event_date,
                NULL AS category_id,
                NULL AS location_id,
                COALESCE(SUM(ti.total_allocated), 0) AS total_tickets,
                COALESCE(SUM(ti.total_sold), 0) AS sold_tickets,
                COALESCE(SUM(ti.total_allocated - ti.total_sold), 0) AS reserved_tickets,
                COALESCE(SUM(ti.total_allocated - ti.total_sold), 0) AS remaining_tickets,
                CASE
                    WHEN COALESCE(SUM(ti.total_allocated), 0) = 0 THEN 'unavailable'
                    WHEN COALESCE(SUM(ti.total_allocated - ti.total_sold), 0) = 0 THEN 'sold_out'
                    WHEN COALESCE(SUM(ti.total_allocated - ti.total_sold), 0) <= COALESCE(SUM(ti.low_stock_threshold), 0) THEN 'low_stock'
                    ELSE 'available'
                END AS availability_status
            FROM events e
            LEFT JOIN ticket_inventory ti ON ti.event_id = e.id
            WHERE e.start_datetime IS NOT NULL
              AND e.deleted_at IS NULL
            GROUP BY
                e.id,
                e.status,
                DATE(e.start_datetime)
        ");

        DB::statement("
            CREATE VIEW calendar_event_availability_view AS
            SELECT
                DATE(e.start_datetime) AS event_date,
                e.id AS event_id,
                e.organizer_id AS organizer_id,
                e.title AS title,
                e.status AS status,
                NULL AS category,
                e.capacity AS event_capacity,
                e.start_datetime AS start_datetime,
                e.end_datetime AS end_datetime,
                COALESCE(SUM(ti.total_allocated), 0) AS total_allocated_sum,
                COALESCE(SUM(ti.total_sold), 0) AS total_sold_sum,
                COALESCE(SUM(ti.total_allocated - ti.total_sold), 0) AS total_remaining_sum,
                CASE
                    WHEN COALESCE(SUM(ti.total_allocated), 0) = 0 THEN 0
                    ELSE (COALESCE(SUM(ti.total_sold), 0) * 100.0) / SUM(ti.total_allocated)
                END AS sell_through_pct,
                0 AS min_price,
                0 AS max_price,
                CASE
                    WHEN COALESCE(SUM(ti.total_allocated - ti.total_sold), 0) = 0
                         AND COALESCE(SUM(ti.total_allocated), 0) > 0 THEN 1
                    WHEN COALESCE(SUM(ti.total_allocated - ti.total_sold), 0) <= COALESCE(SUM(ti.low_stock_threshold), 0) THEN 2
                    WHEN COALESCE(SUM(ti.total_allocated), 0) = 0 THEN 3
                    ELSE 0
                END AS availability_status
            FROM events e
            LEFT JOIN ticket_inventory ti ON ti.event_id = e.id
            WHERE e.start_datetime IS NOT NULL
              AND e.deleted_at IS NULL
            GROUP BY
                DATE(e.start_datetime),
                e.id,
                e.organizer_id,
                e.title,
                e.status,
                NULL,
                e.capacity,
                e.start_datetime,
                e.end_datetime
        ");

        DB::statement("
            CREATE VIEW calendar_date_availability_summary_view AS
            SELECT
                DATE(e.start_datetime) AS event_date,
                COUNT(*) AS total_events,
                SUM(CASE WHEN e.status = 'published' THEN 1 ELSE 0 END) AS published_events,
                SUM(CASE WHEN e.status = 'draft' THEN 1 ELSE 0 END) AS draft_events,
                SUM(CASE WHEN e.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_events,
                COALESCE(SUM(CASE WHEN e.status = 'published' THEN e.capacity ELSE 0 END), 0) AS total_capacity,
                SUM(CASE
                    WHEN e.status = 'published'
                     AND COALESCE(inv.total_remaining, 0) = 0
                     AND COALESCE(inv.total_allocated, 0) > 0
                    THEN 1 ELSE 0
                END) AS sold_out_events,
                SUM(CASE
                    WHEN e.status = 'published'
                     AND COALESCE(inv.total_remaining, 0) > 0
                    THEN 1 ELSE 0
                END) AS available_events
            FROM events e
            LEFT JOIN (
                SELECT
                    event_id,
                    SUM(total_allocated - total_sold) AS total_remaining,
                    SUM(total_allocated) AS total_allocated
                FROM ticket_inventory
                GROUP BY event_id
            ) inv ON inv.event_id = e.id
            WHERE e.start_datetime IS NOT NULL
              AND e.deleted_at IS NULL
            GROUP BY DATE(e.start_datetime)
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS calendar_events_availability');
        DB::statement('DROP VIEW IF EXISTS calendar_event_availability_view');
        DB::statement('DROP VIEW IF EXISTS calendar_date_availability_summary_view');
    }
};
