<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Non-destructively transform pricing_windows from the original schema
     * to the Step 60 schema without dropping the table or losing rows.
     *
     * Original columns preserved/renamed:
     *   - id                  : UUID PK (unchanged)
     *   - event_id            : bigint FK -> events.id (unchanged)
     *   - name                : renamed -> window_name
     *   - start_date          : renamed -> start_date_time
     *   - end_date            : renamed -> end_date_time
     *   - priority            : integer, default 1 (unchanged)
     *   - is_active           : boolean, default true (unchanged)
     *   - description         : text, nullable (kept, not in target schema but harmless)
     *   - created_at / updated_at : unchanged
     *
     * New columns added (nullable where existing rows have no value):
     *   - ticket_category_id  : unsignedBigInteger, nullable
     *   - price               : decimal(10,2), nullable
     *   - quantity_limit      : integer, nullable
     *   - quantity_sold       : integer, default 0
     *   - deleted_at          : timestamp, nullable (SoftDeletes)
     */
    public function up(): void
    {
        // -----------------------------------------------------------------
        // 1. Temporarily remove dependent FK constraints
        // -----------------------------------------------------------------
        if (Schema::hasTable('ticket_inventory') && Schema::hasColumn('ticket_inventory', 'pricing_window_id')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->dropForeign(['pricing_window_id']);
            });
        }

        if (Schema::hasTable('analytics_sales_timeline') && Schema::hasColumn('analytics_sales_timeline', 'pricing_window_id')) {
            Schema::table('analytics_sales_timeline', function (Blueprint $table) {
                $table->dropForeign(['pricing_window_id']);
            });
        }

        if (Schema::hasTable('inventory_adjustments') && Schema::hasColumn('inventory_adjustments', 'pricing_window_id')) {
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                $table->dropForeign(['pricing_window_id']);
            });
        }

        // -----------------------------------------------------------------
        // 2. Rename existing columns in place (preserves data)
        // -----------------------------------------------------------------
        Schema::table('pricing_windows', function (Blueprint $table) {
            $table->renameColumn('name', 'window_name');
            $table->renameColumn('start_date', 'start_date_time');
            $table->renameColumn('end_date', 'end_date_time');
        });

        // -----------------------------------------------------------------
        // 3. Add new columns
        // -----------------------------------------------------------------
        Schema::table('pricing_windows', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_category_id')->nullable()->after('event_id');
            $table->decimal('price', 10, 2)->nullable()->after('window_name');
            $table->integer('quantity_limit')->nullable()->after('price');
            $table->integer('quantity_sold')->default(0)->after('quantity_limit');
            $table->softDeletes()->after('updated_at');
        });

        // -----------------------------------------------------------------
        // 4. Add new indexes and foreign keys
        // -----------------------------------------------------------------
        Schema::table('pricing_windows', function (Blueprint $table) {
            $table->foreign('ticket_category_id')
                ->references('id')
                ->on('ticket_tiers')
                ->nullOnDelete();

            $table->index('ticket_category_id');
            $table->index(['event_id', 'ticket_category_id']);
            $table->index('start_date_time');
            $table->index('end_date_time');
            $table->index('deleted_at');
            $table->index(['is_active', 'start_date_time', 'end_date_time'], 'idx_windows_active_daterange');
            $table->index(['event_id', 'is_active'], 'idx_windows_event_active');
        });

        // -----------------------------------------------------------------
        // 5. Recreate dependent FKs
        // -----------------------------------------------------------------
        if (Schema::hasTable('ticket_inventory') && Schema::hasColumn('ticket_inventory', 'pricing_window_id')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->foreign('pricing_window_id')
                    ->references('id')
                    ->on('pricing_windows')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('analytics_sales_timeline') && Schema::hasColumn('analytics_sales_timeline', 'pricing_window_id')) {
            Schema::table('analytics_sales_timeline', function (Blueprint $table) {
                $table->foreign('pricing_window_id')
                    ->references('id')
                    ->on('pricing_windows')
                    ->onDelete('set null');
            });
        }

        if (Schema::hasTable('inventory_adjustments') && Schema::hasColumn('inventory_adjustments', 'pricing_window_id')) {
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                $table->foreign('pricing_window_id')
                    ->references('id')
                    ->on('pricing_windows')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        // -----------------------------------------------------------------
        // NOTE: A complete rollback cannot safely reconstruct data entered
        // into the new columns (ticket_category_id, price, quantity_limit,
        // quantity_sold). Those values are lost when this migration is
        // reversed.
        // -----------------------------------------------------------------

        // 1. Drop dependent FKs
        if (Schema::hasTable('ticket_inventory') && Schema::hasColumn('ticket_inventory', 'pricing_window_id')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->dropForeign(['pricing_window_id']);
            });
        }

        if (Schema::hasTable('analytics_sales_timeline') && Schema::hasColumn('analytics_sales_timeline', 'pricing_window_id')) {
            Schema::table('analytics_sales_timeline', function (Blueprint $table) {
                $table->dropForeign(['pricing_window_id']);
            });
        }

        if (Schema::hasTable('inventory_adjustments') && Schema::hasColumn('inventory_adjustments', 'pricing_window_id')) {
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                $table->dropForeign(['pricing_window_id']);
            });
        }

        // 2. Drop indexes created by this migration
        Schema::table('pricing_windows', function (Blueprint $table) {
            $table->dropIndex('idx_windows_event_active');
            $table->dropIndex('idx_windows_active_daterange');
            $table->dropIndex('pricing_windows_deleted_at_index');
            $table->dropIndex('pricing_windows_end_date_time_index');
            $table->dropIndex('pricing_windows_start_date_time_index');
            $table->dropIndex('pricing_windows_event_id_ticket_category_id_index');
            $table->dropIndex('pricing_windows_ticket_category_id_index');
        });

        // 3. Drop new FKs and columns
        Schema::table('pricing_windows', function (Blueprint $table) {
            $table->dropForeign(['ticket_category_id']);
            $table->dropColumn([
                'deleted_at',
                'quantity_sold',
                'quantity_limit',
                'price',
                'ticket_category_id',
            ]);
        });

        // 4. Rename columns back
        Schema::table('pricing_windows', function (Blueprint $table) {
            $table->renameColumn('start_date_time', 'start_date');
            $table->renameColumn('end_date_time', 'end_date');
            $table->renameColumn('window_name', 'name');
        });

        // 5. Recreate dependent FKs
        if (Schema::hasTable('ticket_inventory') && Schema::hasColumn('ticket_inventory', 'pricing_window_id')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->foreign('pricing_window_id')
                    ->references('id')
                    ->on('pricing_windows')
                    ->onDelete('cascade');
            });
        }

        if (Schema::hasTable('analytics_sales_timeline') && Schema::hasColumn('analytics_sales_timeline', 'pricing_window_id')) {
            Schema::table('analytics_sales_timeline', function (Blueprint $table) {
                $table->foreign('pricing_window_id')
                    ->references('id')
                    ->on('pricing_windows')
                    ->onDelete('set null');
            });
        }

        if (Schema::hasTable('inventory_adjustments') && Schema::hasColumn('inventory_adjustments', 'pricing_window_id')) {
            Schema::table('inventory_adjustments', function (Blueprint $table) {
                $table->foreign('pricing_window_id')
                    ->references('id')
                    ->on('pricing_windows')
                    ->onDelete('set null');
            });
        }
    }
};
