<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create a physical summary table that acts as a materialized view
     * for calendar queries. This is refreshed via:
     * 1. EventObserver (real-time on create/update/delete)
     * 2. RefreshCalendarSummary command (scheduled every 5 min)
     *
     * This replaces the events_by_date SQL view for better performance
     * on large datasets (10K+ events).
     */
    public function up(): void
    {
        Schema::create('events_calendar_summary', function (Blueprint $table) {
            $table->id();
            $table->date('event_date');
            $table->unsignedInteger('total_events')->default(0);
            $table->unsignedInteger('total_capacity')->default(0);
            $table->unsignedInteger('published_events')->default(0);
            $table->unsignedInteger('published_capacity')->default(0);
            $table->unsignedInteger('draft_events')->default(0);
            $table->unsignedInteger('cancelled_events')->default(0);
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamps();

            $table->unique('event_date', 'idx_summary_event_date');
        });

        // Seed the summary table with existing data
        DB::statement("
            INSERT INTO events_calendar_summary 
                (event_date, total_events, total_capacity, published_events, published_capacity, draft_events, cancelled_events, last_refreshed_at, created_at, updated_at)
            SELECT 
                DATE(start_datetime) as event_date,
                COUNT(*) as total_events,
                COALESCE(SUM(capacity), 0) as total_capacity,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_events,
                SUM(CASE WHEN status = 'published' THEN COALESCE(capacity, 0) ELSE 0 END) as published_capacity,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_events,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_events,
                CURRENT_TIMESTAMP as last_refreshed_at,
                CURRENT_TIMESTAMP as created_at,
                CURRENT_TIMESTAMP as updated_at
            FROM events
            WHERE start_datetime IS NOT NULL
            GROUP BY DATE(start_datetime)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('events_calendar_summary');
    }
};
