<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function dropAllViews(): void
    {
        $views = [];
        try {
            if (DB::getDriverName() === 'sqlite') {
                $views = array_column(DB::select("SELECT name FROM sqlite_master WHERE type='view'"), 'name');
            }
        } catch (\Throwable) {
            return;
        }
        foreach ($views as $v) {
            try { DB::statement("DROP VIEW IF EXISTS \"{$v}\""); } catch (\Throwable) {}
        }
    }

    private function recreateCalendarAvailabilityView(): void
    {
        $viewName = 'calendar_events_availability';
        if (!Schema::hasTable('events') || !Schema::hasTable('ticket_inventory')) {
            return;
        }
        $eventDateExpr = Schema::hasColumn('events', 'start_datetime')
            ? 'DATE(e.start_datetime)'
            : (Schema::hasColumn('events', 'event_date') ? 'e.event_date' : 'DATE(e.start_date)');

        $cols = ['e.id AS event_id', 'e.status', "{$eventDateExpr} AS event_date"];
        $groupBy = ['e.id', 'e.status', $eventDateExpr];
        if (Schema::hasColumn('events', 'category_id')) { $cols[]='e.category_id'; $groupBy[]='e.category_id'; }
        else { $cols[]='NULL AS category_id'; }
        if (Schema::hasColumn('events', 'location_id')) { $cols[]='e.location_id'; $groupBy[]='e.location_id'; }
        else { $cols[]='NULL AS location_id'; }
        $colList = implode(",\n                ", $cols);
        $grpList = implode(",\n                ", $groupBy);
        $sql = "
            SELECT
                {$colList},
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
            GROUP BY {$grpList}
        ";
        try {
            DB::statement("DROP VIEW IF EXISTS {$viewName}");
            DB::statement("CREATE VIEW {$viewName} AS {$sql}");
        } catch (\Throwable) {
        }
    }

    public function up(): void
    {
        $this->dropAllViews();

        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('start_date', 'start_datetime');
            $table->renameColumn('end_date', 'end_datetime');
        });

        $this->recreateCalendarAvailabilityView();
    }

    public function down(): void
    {
        $this->dropAllViews();

        Schema::table('events', function (Blueprint $table) {
            $table->renameColumn('start_datetime', 'start_date');
            $table->renameColumn('end_datetime', 'end_date');
        });

        $this->recreateCalendarAvailabilityView();
    }
};