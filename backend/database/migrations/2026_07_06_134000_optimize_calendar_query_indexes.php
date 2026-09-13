<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================================
        // 1. EVENTS TABLE INDEXES
        // ============================================================
        // Canonical events schema at this migration timestamp:
        //   - status           : string
        //   - start_date       : timestamp
        //   - organizer_id     : bigint FK -> organizers.id
        //   - category         : does not exist
        //   - start_datetime   : does not exist yet (renamed later)

        Schema::table('events', function (Blueprint $table) {
            $table->index(['status', 'start_date'], 'idx_events_status_start_date');
            $table->index(['start_date'], 'idx_events_start_date');
            $table->index(['organizer_id', 'status', 'start_date'], 'idx_events_organizer_status_date');
        });

        // ============================================================
        // 2. TICKET_INVENTORY TABLE INDEXES
        // ============================================================
        // Canonical ticket_inventory schema:
        //   - event_id         : bigint FK -> events.id
        //   - ticket_tier_id   : bigint FK -> ticket_tiers.id
        //   - total_quantity   : integer
        //   - sold_quantity    : integer
        //   - reserved_quantity: integer
        //
        // The preceding migration 2026_07_06_000006_add_calendar_query_indexes
        // already created:
        //   - ticket_inventory_event_ticket_tier_index (event_id, ticket_tier_id)
        //   - ticket_inventory_event_id_index (event_id)
        // No additional indexes are created here to avoid duplicates.

        // ============================================================
        // 3. PRICING_WINDOWS TABLE INDEXES
        // ============================================================
        // Canonical pricing_windows schema:
        //   - event_id         : bigint FK -> events.id
        //   - start_date       : timestamp
        //   - end_date         : timestamp
        //   - is_active        : boolean
        //   - deleted_at       : does not exist
        //   - ticket_category_id: does not exist
        //
        // The preceding migration 2026_07_06_000006_add_calendar_query_indexes
        // already created:
        //   - pricing_windows_event_id_index (event_id)

        Schema::table('pricing_windows', function (Blueprint $table) {
            $table->index(['event_id', 'start_date', 'end_date'], 'idx_pricing_windows_event_dates_old');
        });

        // ============================================================
        // 4. ORGANIZERS TABLE
        // ============================================================
        // Canonical organizers schema:
        //   - name             : does not exist
        // No index created.

        // ============================================================
        // 5. EVENTS_CALENDAR_SUMMARY
        // ============================================================
        // This table is created by a later migration
        // (2026_07_22_081001_create_events_calendar_summary_table.php),
        // so it does not exist at this point in the migration chain.
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_organizer_status_date');
            $table->dropIndex('idx_events_start_date');
            $table->dropIndex('idx_events_status_start_date');
        });

        Schema::table('pricing_windows', function (Blueprint $table) {
            $table->dropIndex('idx_pricing_windows_event_dates_old');
        });
    }
};
