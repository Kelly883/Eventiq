<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Non-destructive rebuild of ticket_inventory.
     *
     * The TicketInventory model requires UUID primary keys:
     *   - public $incrementing = false
     *   - protected $keyType = 'string'
     *   - booted() auto-generates UUIDs on create
     *
     * Because PostgreSQL cannot change a primary key column type in place,
     * this migration preserves every existing row by:
     *   1. Dropping dependent calendar views.
     *   2. Dropping dependent foreign keys.
     *   3. Capturing existing ticket_inventory data.
     *   4. Rebuilding the table with a UUID primary key.
     *   5. Restoring data with newly generated UUIDs.
     *   6. Updating inventory_adjustments.ticket_inventory_id to the new UUIDs.
     *   7. Recreating foreign keys.
     *
     * No DROP ... CASCADE is used.
     * No production data is deleted.
     */
    public function up(): void
    {
        // -----------------------------------------------------------------
        // 1. Drop dependent calendar views before touching ticket_inventory
        // -----------------------------------------------------------------
        DB::statement('DROP VIEW IF EXISTS calendar_events_availability');
        DB::statement('DROP VIEW IF EXISTS calendar_event_availability_view');
        DB::statement('DROP VIEW IF EXISTS calendar_date_availability_summary_view');

        // -----------------------------------------------------------------
        // 2. Drop dependent foreign keys so ticket_inventory can be rebuilt
        // -----------------------------------------------------------------
        if (Schema::hasTable('inventory_adjustments') && Schema::hasColumn('inventory_adjustments', 'ticket_inventory_id')) {
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                $table->dropForeign(['ticket_inventory_id']);
            });
        }

        // -----------------------------------------------------------------
        // 3. Capture existing ticket_inventory data
        // -----------------------------------------------------------------
        $existing = DB::table('ticket_inventory')->get();

        // Build old_id -> new_uuid mapping
        $idMapping = [];
        foreach ($existing as $row) {
            $idMapping[(int) $row->id] = (string) Str::uuid();
        }

        // -----------------------------------------------------------------
        // 4. Drop the old table and recreate with UUID primary key
        // -----------------------------------------------------------------
        Schema::dropIfExists('ticket_inventory');

        Schema::create('ticket_inventory', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('ticket_tier_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('pricing_window_id')->nullable()->constrained('pricing_windows')->onDelete('cascade');

            // Renamed from total_quantity / sold_quantity
            $table->integer('total_allocated')->default(0);
            $table->integer('total_sold')->default(0);

            // Derived columns. Use stored generated columns for PostgreSQL.
            $table->integer('total_available')->storedAs('total_allocated - total_sold');
            $table->integer('low_stock_threshold')->nullable();
            $table->boolean('is_low_stock')->storedAs(
                'CASE WHEN (total_allocated - total_sold) > 0 AND (total_allocated - total_sold) <= COALESCE(low_stock_threshold, 0) THEN 1 ELSE 0 END'
            );

            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('event_id', 'inv_event_id_idx');
            $table->index('ticket_tier_id', 'inv_tier_id_idx');
            $table->index(['event_id', 'ticket_tier_id'], 'inv_event_tier_idx');
            $table->index(['event_id', 'is_low_stock'], 'inv_event_low_stock_idx');
        });

        // -----------------------------------------------------------------
        // 5. Restore data with new UUIDs
        // -----------------------------------------------------------------
        foreach ($existing as $row) {
            $oldId = (int) $row->id;
            $newId = $idMapping[$oldId];

            DB::table('ticket_inventory')->insert([
                'id' => $newId,
                'event_id' => $row->event_id,
                'ticket_tier_id' => $row->ticket_tier_id,
                'pricing_window_id' => $row->pricing_window_id,
                'total_allocated' => $row->total_quantity,
                'total_sold' => $row->sold_quantity,
                'low_stock_threshold' => 10,
                'last_updated_at' => $row->created_at,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        // -----------------------------------------------------------------
        // 6. Update inventory_adjustments.ticket_inventory_id to new UUIDs
        // -----------------------------------------------------------------
        if (Schema::hasTable('inventory_adjustments') && Schema::hasColumn('inventory_adjustments', 'ticket_inventory_id')) {
            // Add a temporary UUID column
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                $table->uuid('new_ticket_inventory_id')->nullable();
            });

            // Map old BIGINT IDs to new UUIDs
            foreach ($idMapping as $oldId => $newId) {
                DB::table('inventory_adjustments')
                    ->where('ticket_inventory_id', $oldId)
                    ->update(['new_ticket_inventory_id' => $newId]);
            }

            // Drop the old BIGINT column and rename the new one
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                $table->dropForeign(['ticket_inventory_id']);
                $table->dropColumn('ticket_inventory_id');
                $table->renameColumn('new_ticket_inventory_id', 'ticket_inventory_id');
            });

            // Recreate the foreign key against ticket_inventory.id (now UUID)
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                $table->foreign('ticket_inventory_id')
                    ->references('id')
                    ->on('ticket_inventory')
                    ->onDelete('cascade');
            });
        }

        // -----------------------------------------------------------------
        // 7. Calendar views are intentionally NOT recreated here.
        //    Later migrations (2026_07_22_070102, 2026_08_05_000100) will
        //    recreate them with the updated ticket_inventory column names.
        // -----------------------------------------------------------------
    }

    public function down(): void
    {
        // -----------------------------------------------------------------
        // NOTE: Full rollback cannot safely reconstruct the original
        // BIGINT primary keys from the new UUIDs. Reversing this migration
        // would require an explicit reverse mapping that does not exist.
        //
        // Instead, restore the original schema by re-running the original
        // migration 2026_07_05_220000_create_ticket_inventory_table.php,
        // accepting that UUID -> BIGINT translation is lossy without an
        // explicit reverse mapping.
        // -----------------------------------------------------------------
    }
};
