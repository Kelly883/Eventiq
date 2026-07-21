<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_windows', function (Blueprint $table) {
            // Add missing composite indexes for query performance
            $table->index(['is_active', 'start_date_time', 'end_date_time'], 'idx_windows_active_daterange');
            $table->index(['event_id', 'is_active'], 'idx_windows_event_active');

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
            // Drop the new indexes
            $table->dropIndex('idx_windows_active_daterange');
            $table->dropIndex('idx_windows_event_active');

            // Revert FK back to CASCADE
            $table->dropForeign(['ticket_category_id']);
            $table->foreign('ticket_category_id')
                ->references('id')
                ->on('ticket_tiers')
                ->onDelete('cascade');
        });
    }
};

