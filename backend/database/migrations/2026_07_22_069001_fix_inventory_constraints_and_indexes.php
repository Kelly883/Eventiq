<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ==========================================================
        // Fix 1: Rebuild ticket_inventory with RESTRICT on ticket_tier_id FK
        //        + corrected is_low_stock virtual column
        //        + composite (event_id, is_low_stock) index
        // Note: Index names are table-prefixed to avoid SQLite's
        //       database-wide index namespace collision.
        // ==========================================================
        if (Schema::hasTable('ticket_inventory')) {
            // Capture existing data
            $existing = DB::table('ticket_inventory')->get();

            Schema::dropIfExists('ticket_inventory');

            Schema::create('ticket_inventory', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('event_id')->constrained()->onDelete('cascade');
                
                // Changed from onDelete('cascade') to restrict
                // This prevents accidental deletion of a tier that has inventory.
                $table->foreignId('ticket_tier_id')->constrained()->onDelete('restrict');
                
                $table->integer('total_allocated')->default(0);
                $table->integer('total_sold')->default(0);
                $table->integer('total_available')->virtualAs('total_allocated - total_sold');
                $table->integer('low_stock_threshold')->nullable();
                
                // Corrected is_low_stock: only true when stock > 0 AND <= threshold
                // Sold-out (total_available = 0) should NOT be low stock
                $table->boolean('is_low_stock')->virtualAs(
                    "CASE WHEN total_available > 0 AND total_available <= COALESCE(low_stock_threshold, 0) THEN 1 ELSE 0 END"
                );
                
                $table->timestamp('last_updated_at')->nullable();
                $table->timestamps();

                // Indexes with table-prefixed names for SQLite compatibility
                $table->index('event_id', 'inv_event_id_idx');
                $table->index('ticket_tier_id', 'inv_tier_id_idx');
                $table->index(['event_id', 'ticket_tier_id'], 'inv_event_tier_idx');
                
                // NEW: Composite index for low-stock alert queries
                // Covers: WHERE event_id = ? AND is_low_stock = 1
                $table->index(['event_id', 'is_low_stock'], 'inv_event_low_stock_idx');
            });

            // Restore data
            foreach ($existing as $row) {
                DB::table('ticket_inventory')->insert((array) $row);
            }
        }

        // ==========================================================
        // Fix 2: Rebuild inventory_adjustments with nullOnDelete
        //        for ticket_tier_id (preserve audit trail if tier deleted)
        // ==========================================================
        if (Schema::hasTable('inventory_adjustments')) {
            $existing = DB::table('inventory_adjustments')->get();

            Schema::dropIfExists('inventory_adjustments');

            Schema::create('inventory_adjustments', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignId('event_id')->constrained()->onDelete('cascade');
                
                // Changed from cascade to nullOnDelete — preserves audit trail
                // If a tier is deleted, adjustments remain with ticket_tier_id = NULL
                $table->foreignId('ticket_tier_id')->nullable()->constrained()->nullOnDelete();
                
                $table->foreignId('pricing_window_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('organizer_id')->constrained('users')->onDelete('cascade');
                $table->enum('adjustment_type', ['manual_increase', 'manual_decrease', 'reallocation', 'system_correction']);
                $table->integer('quantity_before');
                $table->integer('quantity_after');
                $table->integer('quantity_delta');
                $table->string('reason', 500)->nullable();
                $table->timestamp('created_at')->nullable();

                // Indexes
                $table->index(['event_id', 'created_at'], 'adj_event_created_idx');
                $table->index('organizer_id', 'adj_organizer_idx');
            });

            // Restore data
            foreach ($existing as $row) {
                DB::table('inventory_adjustments')->insert((array) $row);
            }
        }
    }

    public function down(): void
    {
        // Rollback would restore previous schema from the original migration.
        // This is destructive, so we don't implement a full rollback here.
        // Instead, just re-apply the original schema from migration 2026_07_21_030001.
    }
};

