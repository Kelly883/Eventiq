<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composite index for the primary calendar query:
     * WHERE status = 'published' AND is_public = 1 AND start_datetime BETWEEN X AND Y
     *
     * Column order follows selectivity: status (low cardinality) → is_public → start_datetime (range scan).
     * Also creates index for ticket_inventory aggregation (event_id covering index for SUM).
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index(['status', 'is_public', 'start_datetime'], 'idx_events_public_calendar');
        });

        // Ensure ticket_inventory has an index on event_id for the aggregation join
        if (!Schema::hasIndex('ticket_inventory', 'idx_ticket_inventory_event_id')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->index('event_id', 'idx_ticket_inventory_event_id');
            });
        }

        // Index on pricing_windows for the price aggregation
        if (!Schema::hasIndex('pricing_windows', 'idx_pricing_windows_event_active')) {
            Schema::table('pricing_windows', function (Blueprint $table) {
                $table->index(['event_id', 'is_active'], 'idx_pricing_windows_event_active');
            });
        }
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_public_calendar');
        });

        if (Schema::hasIndex('ticket_inventory', 'idx_ticket_inventory_event_id')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->dropIndex('idx_ticket_inventory_event_id');
            });
        }

        if (Schema::hasIndex('pricing_windows', 'idx_pricing_windows_event_active')) {
            Schema::table('pricing_windows', function (Blueprint $table) {
                $table->dropIndex('idx_pricing_windows_event_active');
            });
        }
    }
};
