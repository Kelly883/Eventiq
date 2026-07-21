<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            // Performance indexes
            $table->index('is_active');
            $table->index('sales_end_date');
            $table->index('early_bird_end_date');
            
            // Composite index for active tier queries
            $table->index(['event_id', 'is_active', 'sales_start_date']);
            
            // Unique constraint: prevent duplicate tier names per event
            $table->unique(['event_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropIndex(['ticket_tiers_is_active_index']);
            $table->dropIndex(['ticket_tiers_sales_end_date_index']);
            $table->dropIndex(['ticket_tiers_early_bird_end_date_index']);
            $table->dropIndex(['ticket_tiers_event_id_is_active_sales_start_date_index']);
            $table->dropUnique(['ticket_tiers_event_id_name_unique']);
        });
    }
};