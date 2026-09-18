<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->dropCalendarViews();
        $this->dropTicketInventoryCheckConstraints();
        $this->dropVirtualColumns();
        $this->createCalendarViews();
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $this->dropCalendarViews();
        $this->dropTicketInventoryCheckConstraints();
        Schema::table('ticket_inventory', function (Blueprint $table) {
            $table->integer('total_available')->storedAs('total_allocated - total_sold');
            $table->boolean('is_low_stock')->storedAs(
                "CASE WHEN (total_allocated - total_sold) > 0 AND (total_allocated - total_sold) <= COALESCE(low_stock_threshold, 0) THEN TRUE ELSE FALSE END"
            );
        });
    }

    private function dropCalendarViews(): void
    {
        DB::statement('DROP VIEW IF EXISTS calendar_events_availability');
        DB::statement('DROP VIEW IF EXISTS calendar_event_availability_view');
        DB::statement('DROP VIEW IF EXISTS calendar_date_availability_summary_view');
    }

    private function dropTicketInventoryCheckConstraints(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE ticket_inventory DROP CONSTRAINT IF EXISTS chk_inventory_limits');
        DB::statement('ALTER TABLE ticket_inventory DROP CONSTRAINT IF EXISTS chk_void_limits');
    }

    private function dropVirtualColumns(): void
    {
        $hasTotalAvailable = Schema::hasColumn('ticket_inventory', 'total_available');
        $hasIsLowStock = Schema::hasColumn('ticket_inventory', 'is_low_stock');

        if ($hasTotalAvailable || $hasIsLowStock) {
            Schema::table('ticket_inventory', function (Blueprint $table) use ($hasTotalAvailable, $hasIsLowStock) {
                $columns = [];
                if ($hasTotalAvailable) {
                    $columns[] = 'total_available';
                }
                if ($hasIsLowStock) {
                    $columns[] = 'is_low_stock';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }

    private function createCalendarViews(): void
    {
        DB::statement('DROP VIEW IF EXISTS calendar_events_availability');

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
                    WHEN COALESCE(SUM(ti.total_allocated - ti.total_sold), 0) <= COALESCE(SUM(COALESCE(ti.low_stock_threshold, 0)), 0) THEN 'low_stock'
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

        DB::statement('DROP VIEW IF EXISTS calendar_event_availability_view');

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
                e.start_datetime AS end_datetime,
                COALESCE(SUM(ti.total_allocated), 0) AS total_allocated_sum,
                COALESCE(SUM(ti.total_sold), 0) AS total_sold_sum,
                COALESCE(SUM(ti.total_allocated - ti.total_sold), 0) AS total_remaining_sum,
                CASE
                    WHEN COALESCE(SUM(ti.total_allocated), 0) = 0 THEN 0
                    ELSE (COALESCE(SUM(ti.total_sold), 0) * 100.0) / SUM(ti.total_allocated)
                END AS sell_through_pct,
                COALESCE(MIN(pw.price), 0) AS min_price,
                COALESCE(MAX(pw.price), 0) AS max_price,
                CASE
                    WHEN COALESCE(SUM(ti.total_allocated - ti.total_sold), 0) = 0
                         AND COALESCE(SUM(ti.total_allocated), 0) > 0 THEN 1
                    WHEN COALESCE(SUM(ti.total_allocated - ti.total_sold), 0) <= COALESCE(SUM(COALESCE(ti.low_stock_threshold, 0)), 0) THEN 2
                    WHEN COALESCE(SUM(ti.total_allocated), 0) = 0 THEN 3
                    ELSE 0
                END AS availability_status
            FROM events e
            LEFT JOIN ticket_inventory ti ON ti.event_id = e.id
            LEFT JOIN pricing_windows pw
                ON pw.event_id = e.id
               AND pw.is_active = TRUE
               AND pw.deleted_at IS NULL
               AND CURRENT_DATE BETWEEN DATE(pw.start_date_time) AND DATE(pw.end_date_time)
            WHERE e.start_datetime IS NOT NULL
              AND e.deleted_at IS NULL
            GROUP BY
                DATE(e.start_datetime),
                e.id,
                e.organizer_id,
                e.title,
                e.status,
                e.capacity
        ");
