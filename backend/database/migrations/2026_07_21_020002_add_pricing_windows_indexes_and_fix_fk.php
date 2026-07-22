<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_windows', function (Blueprint $table) {
            // Note: The composite indexes (idx_windows_active_daterange, idx_windows_event_active)
            // are already created by migration 2026_07_20_010014_create_pricing_windows_table_for_step60
            // during table creation. No need to recreate.

            // Drop the old cascade FK on ticket_category_id and recreate with SET NULL
            $table->dropForeign(['ticket_category_id']);
            $table->foreign('ticket_category_id')
                ->references('id')
                ->on('ticket_tiers')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('pricing_windows', function (Blueprint $table) {
            // Revert FK back to CASCADE
            $table->dropForeign(['ticket_category_id']);
            $table->foreign('ticket_category_id')
                ->references('id')
                ->on('ticket_tiers')
                ->onDelete('cascade');
        });
    }
};

