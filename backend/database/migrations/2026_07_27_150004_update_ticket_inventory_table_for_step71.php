<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates ticket_inventory table for Step 71 check-in system:
     * - Adds total_checked_in field
     * - Adds total_void field
     * - Adds index on event_id
     */
    public function up(): void
    {
        Schema::table('ticket_inventory', function (Blueprint $table) {
            // Add total_checked_in field
            if (!Schema::hasColumn('ticket_inventory', 'total_checked_in')) {
                $table->integer('total_checked_in')->default(0)->after('total_available');
            }

            // Add total_void field
            if (!Schema::hasColumn('ticket_inventory', 'total_void')) {
                $table->integer('total_void')->default(0)->after('total_checked_in');
            }

            // Ensure event_id index exists
            try {
                if (!Schema::hasIndex('ticket_inventory', 'idx_ticket_inventory_event')) {
                    $table->index('event_id', 'idx_ticket_inventory_event');
                }
            } catch (\Exception $e) {
                // Index may already exist
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_inventory', function (Blueprint $table) {
            // Drop added columns
            if (Schema::hasColumn('ticket_inventory', 'total_void')) {
                $table->dropColumn('total_void');
            }

            if (Schema::hasColumn('ticket_inventory', 'total_checked_in')) {
                $table->dropColumn('total_checked_in');
            }

            // Drop index
            try {
                $table->dropIndex('idx_ticket_inventory_event');
            } catch (\Exception $e) {
                // Index may not exist
            }
        });
    }
};