<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add covering indexes for calendar queries to improve performance
     * on large datasets (10K+ events).
     */
    public function up(): void
    {
        // Get existing indexes on events table to avoid duplicates
        $existingEvents = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='events'");
        $existingEventsNames = array_map(fn($r) => $r->name, $existingEvents);

        Schema::table('events', function (Blueprint $table) use ($existingEventsNames) {
            // Covering index for the events_by_date view: status + date + capacity
            if (!in_array('idx_events_status_date_capacity', $existingEventsNames)) {
                $table->index(['status', 'start_datetime', 'capacity'], 'idx_events_status_date_capacity');
            }

            // Composite index for organizer dashboard: organizer + status + date
            if (!in_array('idx_events_organizer_status_date', $existingEventsNames)) {
                $table->index(['organizer_id', 'status', 'start_datetime'], 'idx_events_organizer_status_date');
            }
        });

        // ticket_inventory covering index for availability queries
        if (Schema::hasTable('ticket_inventory')) {
            $existingInv = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='ticket_inventory'");
            $existingInvNames = array_map(fn($r) => $r->name, $existingInv);

            Schema::table('ticket_inventory', function (Blueprint $table) use ($existingInvNames) {
                if (!in_array('inv_event_available_sold_idx', $existingInvNames)) {
                    $table->index(['event_id', 'total_available', 'total_sold'], 'inv_event_available_sold_idx');
                }
            });
        }

        // pricing_windows covering index for price range queries in calendar
        if (Schema::hasTable('pricing_windows')) {
            $existingPw = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='pricing_windows'");
            $existingPwNames = array_map(fn($r) => $r->name, $existingPw);

            Schema::table('pricing_windows', function (Blueprint $table) use ($existingPwNames) {
                if (!in_array('idx_pw_event_active_price', $existingPwNames)) {
                    $table->index(['event_id', 'is_active', 'price'], 'idx_pw_event_active_price');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_status_date_capacity');
            $table->dropIndex('idx_events_organizer_status_date');
        });

        if (Schema::hasTable('ticket_inventory')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->dropIndex('inv_event_available_sold_idx');
            });
        }

        if (Schema::hasTable('pricing_windows')) {
            Schema::table('pricing_windows', function (Blueprint $table) {
                $table->dropIndex('idx_pw_event_active_price');
            });
        }
    }
};
