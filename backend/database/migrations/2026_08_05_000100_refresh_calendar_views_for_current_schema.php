<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('events') || !Schema::hasTable('ticket_inventory')) {
            return;
        }

        $eventStartColumn = Schema::hasColumn('events', 'start_datetime') ? 'start_datetime' : 'start_date';
        $eventEndColumn = Schema::hasColumn('events', 'end_datetime') ? 'end_datetime' : 'end_date';
        $eventDeletedPredicate = Schema::hasColumn('events', 'deleted_at') ? 'AND e.deleted_at IS NULL' : '';
        $eventCategoryExpression = Schema::hasColumn('events', 'category') ? 'e.category' : 'NULL';

        $inventoryAllocatedColumn = Schema::hasColumn('ticket_inventory', 'total_allocated')
            ? 'total_allocated'
            : 'total_quantity';
        $inventorySoldColumn = Schema::hasColumn('ticket_inventory', 'total_sold')
            ? 'total_sold'
            : 'sold_quantity';
        $inventoryReservedColumn = Schema::hasColumn('ticket_inventory', 'reserved_quantity')
            ? 'reserved_quantity'
            : null;
        $inventoryRemainingSelectExpression = Schema::hasColumn('ticket_inventory', 'total_available')
            ? 'ti.total_available'
            : ($inventoryReservedColumn
                ? "ti.{$inventoryAllocatedColumn} - ti.{$inventorySoldColumn} - ti.{$inventoryReservedColumn}"
                : "ti.{$inventoryAllocatedColumn} - ti.{$inventorySoldColumn}");
        $inventoryRemainingAggregateExpression = Schema::hasColumn('ticket_inventory', 'total_available')
            ? 'total_available'
            : ($inventoryReservedColumn
                ? "{$inventoryAllocatedColumn} - {$inventorySoldColumn} - {$inventoryReservedColumn}"
                : "{$inventoryAllocatedColumn} - {$inventorySoldColumn}");
        $inventoryReservedSelectExpression = $inventoryReservedColumn ? "ti.{$inventoryReservedColumn}" : '0';
        $inventoryLowStockExpression = Schema::hasColumn('ticket_inventory', 'low_stock_threshold')
            ? 'COALESCE(SUM(ti.low_stock_threshold), 0)'
            : '10';

        $hasPricingWindows = Schema::hasTable('pricing_windows') && Schema::hasColumn('pricing_windows', 'event_id');
        $hasPricingWindowsPrice = $hasPricingWindows && Schema::hasColumn('pricing_windows', 'price');
        $pricingStartColumn = Schema::hasColumn('pricing_windows', 'start_date_time')
            ? 'start_date_time'
            : (Schema::hasColumn('pricing_windows', 'start_date') ? 'start_date' : null);
        $pricingEndColumn = Schema::hasColumn('pricing_windows', 'end_date_time')
            ? 'end_date_time'
            : (Schema::hasColumn('pricing_windows', 'end_date') ? 'end_date' : null);
        $pricingDeletedPredicate = Schema::hasColumn('pricing_windows', 'deleted_at') ? 'AND pw.deleted_at IS NULL' : '';
        $pricingDatePredicate = ($pricingStartColumn && $pricingEndColumn)
            ? "AND DATE('now') BETWEEN DATE(pw.{$pricingStartColumn}) AND DATE(pw.{$pricingEndColumn})"
            : '';
        $pricingJoin = $hasPricingWindowsPrice
            ? "LEFT JOIN pricing_windows pw
                   ON pw.event_id = e.id
                  AND pw.is_active = 1
                  {$pricingDeletedPredicate}
                  {$pricingDatePredicate}"
            : '';
        $minPriceExpression = $hasPricingWindowsPrice ? 'COALESCE(MIN(pw.price), 0)' : '0';
        $maxPriceExpression = $hasPricingWindowsPrice ? 'COALESCE(MAX(pw.price), 0)' : '0';

        $this->dropViews();

        DB::statement("
            CREATE VIEW calendar_events_availability AS
            SELECT
                e.id AS event_id,
                e.status,
                DATE(e.{$eventStartColumn}) AS event_date,
                NULL AS category_id,
                NULL AS location_id,
                COALESCE(SUM(ti.{$inventoryAllocatedColumn}), 0) AS total_tickets,
                COALESCE(SUM(ti.{$inventorySoldColumn}), 0) AS sold_tickets,
                COALESCE(SUM({$inventoryReservedSelectExpression}), 0) AS reserved_tickets,
                COALESCE(SUM({$inventoryRemainingSelectExpression}), 0) AS remaining_tickets,
                CASE
                    WHEN COALESCE(SUM(ti.{$inventoryAllocatedColumn}), 0) = 0 THEN 'unavailable'
                    WHEN COALESCE(SUM({$inventoryRemainingSelectExpression}), 0) = 0 THEN 'sold_out'
                    WHEN COALESCE(SUM({$inventoryRemainingSelectExpression}), 0) <= {$inventoryLowStockExpression} THEN 'low_stock'
                    ELSE 'available'
                END AS availability_status
            FROM events e
            LEFT JOIN ticket_inventory ti ON ti.event_id = e.id
            WHERE e.{$eventStartColumn} IS NOT NULL
              {$eventDeletedPredicate}
            GROUP BY
                e.id,
                e.status,
                DATE(e.{$eventStartColumn})
        ");

        DB::statement("
            CREATE VIEW calendar_event_availability_view AS
            SELECT
                DATE(e.{$eventStartColumn}) AS event_date,
                e.id AS event_id,
                e.organizer_id AS organizer_id,
                e.title AS title,
                e.status AS status,
                {$eventCategoryExpression} AS category,
                e.capacity AS event_capacity,
                e.{$eventStartColumn} AS start_datetime,
                e.{$eventEndColumn} AS end_datetime,
                COALESCE(SUM(ti.{$inventoryAllocatedColumn}), 0) AS total_allocated_sum,
                COALESCE(SUM(ti.{$inventorySoldColumn}), 0) AS total_sold_sum,
                COALESCE(SUM({$inventoryRemainingSelectExpression}), 0) AS total_remaining_sum,
                CASE
                    WHEN COALESCE(SUM(ti.{$inventoryAllocatedColumn}), 0) = 0 THEN 0
                    ELSE (COALESCE(SUM(ti.{$inventorySoldColumn}), 0) * 100.0) / SUM(ti.{$inventoryAllocatedColumn})
                END AS sell_through_pct,
                {$minPriceExpression} AS min_price,
                {$maxPriceExpression} AS max_price,
                CASE
                    WHEN COALESCE(SUM({$inventoryRemainingSelectExpression}), 0) = 0
                         AND COALESCE(SUM(ti.{$inventoryAllocatedColumn}), 0) > 0 THEN 1
                    WHEN COALESCE(SUM({$inventoryRemainingSelectExpression}), 0) <= {$inventoryLowStockExpression} THEN 2
                    WHEN COALESCE(SUM(ti.{$inventoryAllocatedColumn}), 0) = 0 THEN 3
                    ELSE 0
                END AS availability_status
            FROM events e
            LEFT JOIN ticket_inventory ti ON ti.event_id = e.id
            {$pricingJoin}
            WHERE e.{$eventStartColumn} IS NOT NULL
              {$eventDeletedPredicate}
            GROUP BY
                DATE(e.{$eventStartColumn}),
                e.id,
                e.organizer_id,
                e.title,
                e.status,
                {$eventCategoryExpression},
                e.capacity,
                e.{$eventStartColumn},
                e.{$eventEndColumn}
        ");

        DB::statement("
            CREATE VIEW calendar_date_availability_summary_view AS
            SELECT
                DATE(e.{$eventStartColumn}) AS event_date,
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
                    SUM({$inventoryRemainingAggregateExpression}) AS total_remaining,
                    SUM({$inventoryAllocatedColumn}) AS total_allocated
                FROM ticket_inventory
                GROUP BY event_id
            ) inv ON inv.event_id = e.id
            WHERE e.{$eventStartColumn} IS NOT NULL
              {$eventDeletedPredicate}
            GROUP BY DATE(e.{$eventStartColumn})
        ");
    }

    public function down(): void
    {
        $this->dropViews();
    }

    private function dropViews(): void
    {
        DB::statement('DROP VIEW IF EXISTS calendar_events_availability');
        DB::statement('DROP VIEW IF EXISTS calendar_event_availability_view');
        DB::statement('DROP VIEW IF EXISTS calendar_date_availability_summary_view');
    }
};
