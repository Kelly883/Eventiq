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
        $hasTicketInventoryTotalCheckedIn = Schema::hasColumn('ticket_inventory', 'total_checked_in');
        $hasTicketInventoryTotalVoid = Schema::hasColumn('ticket_inventory', 'total_void');
        $hasTicketInventoryIdxTicketInventoryEventIndex = Schema::hasIndex('ticket_inventory', 'idx_ticket_inventory_event');
        Schema::table('ticket_inventory', function (Blueprint $table) use ($hasTicketInventoryTotalCheckedIn, $hasTicketInventoryTotalVoid, $hasTicketInventoryIdxTicketInventoryEventIndex) {
            // Add total_checked_in field
            if (!$hasTicketInventoryTotalCheckedIn) {
                $table->integer('total_checked_in')->default(0)->after('total_available');
            }

            // Add total_void field
            if (!$hasTicketInventoryTotalVoid) {
                $table->integer('total_void')->default(0)->after('total_checked_in');
            }

            // Ensure event_id index exists
            try {
                if (!$hasTicketInventoryIdxTicketInventoryEventIndex) {
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
        $hasTicketInventoryTotalVoid = Schema::hasColumn('ticket_inventory', 'total_void');
        $hasTicketInventoryTotalCheckedIn = Schema::hasColumn('ticket_inventory', 'total_checked_in');
        Schema::table('ticket_inventory', function (Blueprint $table) use ($hasTicketInventoryTotalVoid, $hasTicketInventoryTotalCheckedIn) {
            // Drop added columns
            if ($hasTicketInventoryTotalVoid) {
                $table->dropColumn('total_void');
            }

            if ($hasTicketInventoryTotalCheckedIn) {
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