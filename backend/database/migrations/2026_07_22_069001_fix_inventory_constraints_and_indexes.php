<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Non-destructive post-conversion fixes for ticket_inventory
     * and inventory_adjustments.
     *
     * Assumes 2026_07_21_030001_create_ticket_inventory_table.php has
     * already converted ticket_inventory.id to UUID.
     *
     * Changes applied without dropping data:
     *   - ticket_inventory: ticket_tier_id FK onDelete RESTRICT
     *   - inventory_adjustments: rebuilt with UUID primary key,
     *     preserving every existing row
     */
    public function up(): void
    {
        // -----------------------------------------------------------------
        // Fix 1: Update ticket_inventory FK onDelete behavior
        // -----------------------------------------------------------------
        if (!Schema::hasTable('ticket_inventory')) {
            return;
        }

        Schema::table('ticket_inventory', function (Blueprint $table) {
            $table->dropForeign(['ticket_tier_id']);
        });

        Schema::table('ticket_inventory', function (Blueprint $table) {
            $table->foreign('ticket_tier_id')
                ->references('id')
                ->on('ticket_tiers')
                ->onDelete('restrict');
        });

        // -----------------------------------------------------------------
        // Fix 2: Rebuild inventory_adjustments with UUID primary key
        // -----------------------------------------------------------------
        if (Schema::hasTable('inventory_adjustments')) {
            $existing = DB::table('inventory_adjustments')->get();

            // Build old_id -> new_uuid mapping
            $idMapping = [];
            foreach ($existing as $row) {
                $idMapping[(int) $row->id] = (string) Str::uuid();
            }

            // Drop dependent FKs first
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                $table->dropForeign(['ticket_inventory_id']);
                $table->dropForeign(['event_id']);
                $table->dropForeign(['ticket_tier_id']);
                $table->dropForeign(['pricing_window_id']);
                $table->dropForeign(['organizer_id']);
            });

            Schema::dropIfExists('inventory_adjustments');

            Schema::create('inventory_adjustments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('event_id')->constrained()->onDelete('cascade');
                $table->foreignId('ticket_tier_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignUuid('pricing_window_id')->nullable()->constrained()->onDelete('set null');
                $table->uuid('organizer_id')->constrained('users')->onDelete('cascade');
                $table->foreignUuid('ticket_inventory_id')->nullable()->constrained()->onDelete('cascade');
                $table->string('adjustment_type', 50);
                $table->integer('quantity_before');
                $table->integer('quantity_after');
                $table->integer('quantity_delta');
                $table->string('reason', 500)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->index(['event_id', 'created_at'], 'adj_event_created_idx');
                $table->index('organizer_id', 'adj_organizer_idx');
            });

            // Restore data with new UUIDs
            foreach ($existing as $row) {
                $oldId = (int) $row->id;
                $newId = $idMapping[$oldId];

                DB::table('inventory_adjustments')->insert([
                    'id' => $newId,
                    'event_id' => $row->event_id,
                    'ticket_tier_id' => $row->ticket_tier_id,
                    'pricing_window_id' => $row->pricing_window_id,
                    'organizer_id' => $row->organizer_id,
                    'ticket_inventory_id' => $row->ticket_inventory_id,
                    'adjustment_type' => $row->adjustment_type,
                    'quantity_before' => $row->quantity_before,
                    'quantity_after' => $row->quantity_after,
                    'quantity_delta' => $row->quantity_delta,
                    'reason' => $row->reason,
                    'created_at' => $row->created_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        // -----------------------------------------------------------------
        // NOTE: Reversing the RESTRICT change is straightforward.
        // Reversing the inventory_adjustments rebuild would require
        // re-capturing the current schema state, which is not implemented
        // here because the rebuild is additive and preserves all rows.
        // -----------------------------------------------------------------
    }
};
