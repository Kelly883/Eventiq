<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Sync existing available_count values
        DB::statement('UPDATE ticket_tiers SET available_count = CAST(quantity AS INTEGER) - CAST(sold_count AS INTEGER) WHERE quantity IS NOT NULL');
        DB::statement('UPDATE ticket_tiers SET available_count = 0 WHERE quantity IS NULL OR available_count < 0');

        // 2. Add CHECK constraint: sold_count must never exceed quantity
        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE ticket_tiers ADD CONSTRAINT ticket_tiers_sold_count_check CHECK (sold_count <= quantity)');
            } elseif (DB::getDriverName() === 'sqlite') {
                // SQLite 3.38+ supports ALTER TABLE ADD CONSTRAINT for CHECK
                // Fallback: we handle this in the observer and application layer
                DB::statement('ALTER TABLE ticket_tiers ADD CONSTRAINT ticket_tiers_sold_count_check CHECK (sold_count <= quantity)');
            }
        } catch (\Exception $e) {
            // If the driver doesn't support ALTER TABLE ADD CONSTRAINT,
            // the constraint will be enforced at the application/observer layer
        }

        Schema::table('ticket_tiers', function (Blueprint $table) {
            // 3. Add deleted_at index for soft-delete filtered queries
            //    This speeds up queries like: WHERE deleted_at IS NULL
            $table->index('deleted_at', 'idx_ticket_tiers_deleted_at');

            // 4. Composite index for soft-delete scoped queries by event
            //    Covers: WHERE event_id = ? AND deleted_at IS NULL
            $table->index(['event_id', 'deleted_at'], 'idx_ticket_tiers_event_deleted_at');

            // 5. Composite index for partition-style queries by event
            //    Covers: WHERE event_id = ? ORDER BY id, or JOINs on event_id + id
            $table->index(['event_id', 'id'], 'idx_ticket_tiers_event_id_partition');

            // 6. Composite index for inventory/availability checks
            //    Covers: WHERE event_id = ? AND is_active = ? AND sales_start_date <= ? AND sales_end_date >= ?
            $table->index(['event_id', 'is_active', 'sales_start_date', 'sales_end_date'], 'idx_ticket_tiers_availability_check');

            // 7. Add a comment on available_count noting it should be a generated column
            //    For MySQL production migration:
            //    ALTER TABLE ticket_tiers MODIFY COLUMN available_count INT UNSIGNED
            //        GENERATED ALWAYS AS (GREATEST(0, quantity - sold_count)) STORED;
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropIndex('idx_ticket_tiers_deleted_at');
            $table->dropIndex('idx_ticket_tiers_event_deleted_at');
            $table->dropIndex('idx_ticket_tiers_event_id_partition');
            $table->dropIndex('idx_ticket_tiers_availability_check');
        });

        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE ticket_tiers DROP CHECK ticket_tiers_sold_count_check');
            }
        } catch (\Exception $e) {
            // Best-effort drop
        }
    }
};

