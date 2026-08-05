<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates a database view that collapses per-date event aggregates with
     * availability data (inventory remaining, min/max pricing).  Designed
     * to back the calendar grid — each row = one date + event pair.
     *
     * Usage (Eloquent):
     *   DB::table('calendar_event_availability_view')
     *     ->whereBetween('event_date', [$start, $end])
     *     ->where('status', 'published')
     *     ->get();
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite — simpler dialect, no IFNULL → use COALESCE.
            DB::statement('
                CREATE VIEW calendar_event_availability_view AS
                SELECT
                    DATE(e.start_datetime)                                        AS event_date,
                    e.id                                                          AS event_id,
                    e.organizer_id                                                AS organizer_id,
                    e.title                                                       AS title,
                    e.status                                                      AS status,
                    e.category                                                    AS category,
                    e.capacity                                                    AS event_capacity,
                    e.start_datetime                                              AS start_datetime,
                    e.end_datetime                                                AS end_datetime,
                    COALESCE(SUM(ti.total_allocated), 0)                         AS total_allocated_sum,
                    COALESCE(SUM(ti.total_sold), 0)                              AS total_sold_sum,
                    COALESCE(SUM(ti.total_allocated) - SUM(ti.total_sold), 0)    AS total_remaining_sum,
                    CASE
                        WHEN COALESCE(SUM(ti.total_allocated), 0) = 0 THEN 0
                        ELSE CAST(
                            (COALESCE(SUM(ti.total_sold), 0) * 100.0)
                                / SUM(ti.total_allocated)
                            AS REAL)
                    END                                                           AS sell_through_pct,
                    COALESCE(MIN(pw.price), 0)                                   AS min_price,
                    COALESCE(MAX(pw.price), 0)                                   AS max_price,
                    CASE
                        WHEN COALESCE(SUM(ti.total_allocated) - SUM(ti.total_sold), 0) = 0
                             AND COALESCE(SUM(ti.total_allocated), 0) > 0
                            THEN 1  -- SOLD OUT
                        WHEN COALESCE(SUM(ti.total_allocated) - SUM(ti.total_sold), 0)
                             <= COALESCE(SUM(ti.low_stock_threshold), 0)
                            THEN 2  -- LOW STOCK
                        WHEN COALESCE(SUM(ti.total_allocated), 0) = 0
                            THEN 3  -- NO INVENTORY CONFIGURED
                        ELSE 0         -- AVAILABLE
                    END                                                           AS availability_status
                FROM events e
                LEFT JOIN ticket_inventory ti  ON ti.event_id = e.id
                LEFT JOIN pricing_windows pw
                       ON pw.event_id = e.id
                      AND pw.is_active = 1
                      AND pw.deleted_at IS NULL
                      AND DATE(\'now\') BETWEEN DATE(pw.start_date_time)
                                             AND DATE(pw.end_date_time)
                WHERE e.start_datetime IS NOT NULL
                  AND e.deleted_at IS NULL
                GROUP BY
                    DATE(e.start_datetime),
                    e.id,
                    e.organizer_id,
                    e.title,
                    e.status,
                    e.category,
                    e.capacity,
                    e.start_datetime,
                    e.end_datetime
            ');
        } else {
            // MySQL / PostgreSQL — standard SQL dialect with IFNULL support.
            DB::statement('
                CREATE OR REPLACE VIEW calendar_event_availability_view AS
                SELECT
                    DATE(e.start_datetime)                                        AS event_date,
                    e.id                                                          AS event_id,
                    e.organizer_id                                                AS organizer_id,
                    e.title                                                       AS title,
                    e.status                                                      AS status,
                    e.category                                                    AS category,
                    e.capacity                                                    AS event_capacity,
                    e.start_datetime                                              AS start_datetime,
                    e.end_datetime                                                AS end_datetime,
                    COALESCE(SUM(ti.total_allocated), 0)                         AS total_allocated_sum,
                    COALESCE(SUM(ti.total_sold), 0)                              AS total_sold_sum,
                    COALESCE(SUM(ti.total_allocated) - SUM(ti.total_sold), 0)    AS total_remaining_sum,
                    CASE
                        WHEN COALESCE(SUM(ti.total_allocated), 0) = 0 THEN 0
                        ELSE (COALESCE(SUM(ti.total_sold), 0) * 100.0)
                                / SUM(ti.total_allocated)
                    END                                                           AS sell_through_pct,
                    COALESCE(MIN(pw.price), 0)                                   AS min_price,
                    COALESCE(MAX(pw.price), 0)                                   AS max_price,
                    CASE
                        WHEN COALESCE(SUM(ti.total_allocated) - SUM(ti.total_sold), 0) = 0
                             AND COALESCE(SUM(ti.total_allocated), 0) > 0
                            THEN 1
                        WHEN COALESCE(SUM(ti.total_allocated) - SUM(ti.total_sold), 0)
                             <= COALESCE(SUM(ti.low_stock_threshold), 0)
                            THEN 2
                        WHEN COALESCE(SUM(ti.total_allocated), 0) = 0
                            THEN 3
                        ELSE 0
                    END                                                           AS availability_status
                FROM events e
                LEFT JOIN ticket_inventory ti  ON ti.event_id = e.id
                LEFT JOIN pricing_windows pw
                       ON pw.event_id = e.id
                      AND pw.is_active = TRUE
                      AND pw.deleted_at IS NULL
                      AND CURRENT_DATE BETWEEN DATE(pw.start_date_time)
                                            AND DATE(pw.end_date_time)
                WHERE e.start_datetime IS NOT NULL
                  AND e.deleted_at IS NULL
                GROUP BY
                    DATE(e.start_datetime),
                    e.id,
                    e.organizer_id,
                    e.title,
                    e.status,
                    e.category,
                    e.capacity,
                    e.start_datetime,
                    e.end_datetime
            ');
        }

        // -----------------------------------------------------------------
        // Second view: Per-date rollup (no event breakdown) — powers the
        // calendar month grid "dot" indicators.  One row per calendar date.
        // -----------------------------------------------------------------
        if ($driver === 'sqlite') {
            DB::statement('
                CREATE VIEW calendar_date_availability_summary_view AS
                SELECT
                    DATE(e.start_datetime)                                        AS event_date,
                    COUNT(*)                                                      AS total_events,
                    SUM(CASE WHEN e.status = \'published\' THEN 1 ELSE 0 END)    AS published_events,
                    SUM(CASE WHEN e.status = \'draft\'     THEN 1 ELSE 0 END)    AS draft_events,
                    SUM(CASE WHEN e.status = \'cancelled\' THEN 1 ELSE 0 END)   AS cancelled_events,
                    COALESCE(SUM(CASE WHEN e.status = \'published\'
                                      THEN e.capacity ELSE 0 END), 0)            AS total_capacity,
                    SUM(CASE
                          WHEN e.status = \'published\'
                           AND COALESCE(ti_avail.total_remaining, 0) = 0
                           AND COALESCE(ti_alloc.total_allocated, 0) > 0
                          THEN 1 ELSE 0
                        END)                                                    AS sold_out_events,
                    SUM(CASE
                          WHEN e.status = \'published\'
                           AND COALESCE(ti_avail.total_remaining, 0) > 0
                          THEN 1 ELSE 0
                        END)                                                    AS available_events
                FROM events e
                LEFT JOIN (
                    SELECT event_id, SUM(total_allocated - total_sold) AS total_remaining
                    FROM ticket_inventory
                    GROUP BY event_id
                ) ti_avail ON ti_avail.event_id = e.id
                LEFT JOIN (
                    SELECT event_id, SUM(total_allocated) AS total_allocated
                    FROM ticket_inventory
                    GROUP BY event_id
                ) ti_alloc ON ti_alloc.event_id = e.id
                WHERE e.start_datetime IS NOT NULL
                  AND e.deleted_at IS NULL
                GROUP BY DATE(e.start_datetime)
            ');
        } else {
            DB::statement('
                CREATE OR REPLACE VIEW calendar_date_availability_summary_view AS
                SELECT
                    DATE(e.start_datetime)                                        AS event_date,
                    COUNT(*)                                                      AS total_events,
                    SUM(CASE WHEN e.status = \'published\' THEN 1 ELSE 0 END)    AS published_events,
                    SUM(CASE WHEN e.status = \'draft\'     THEN 1 ELSE 0 END)    AS draft_events,
                    SUM(CASE WHEN e.status = \'cancelled\' THEN 1 ELSE 0 END)   AS cancelled_events,
                    COALESCE(SUM(CASE WHEN e.status = \'published\'
                                      THEN e.capacity ELSE 0 END), 0)            AS total_capacity,
                    SUM(CASE
                          WHEN e.status = \'published\'
                           AND COALESCE(ti_avail.total_remaining, 0) = 0
                           AND COALESCE(ti_alloc.total_allocated, 0) > 0
                          THEN 1 ELSE 0
                        END)                                                    AS sold_out_events,
                    SUM(CASE
                          WHEN e.status = \'published\'
                           AND COALESCE(ti_avail.total_remaining, 0) > 0
                          THEN 1 ELSE 0
                        END)                                                    AS available_events
                FROM events e
                LEFT JOIN (
                    SELECT event_id, SUM(total_allocated - total_sold) AS total_remaining
                    FROM ticket_inventory
                    GROUP BY event_id
                ) ti_avail ON ti_avail.event_id = e.id
                LEFT JOIN (
                    SELECT event_id, SUM(total_allocated) AS total_allocated
                    FROM ticket_inventory
                    GROUP BY event_id
                ) ti_alloc ON ti_alloc.event_id = e.id
                WHERE e.start_datetime IS NOT NULL
                  AND e.deleted_at IS NULL
                GROUP BY DATE(e.start_datetime)
            ');
        }
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS calendar_event_availability_view');
        DB::statement('DROP VIEW IF EXISTS calendar_date_availability_summary_view');
    }
};
