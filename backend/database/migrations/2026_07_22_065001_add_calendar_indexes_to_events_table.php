<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for fast calendar queries on events table.
     */
    public function up(): void
    {
        // Check if indexes already exist before creating them
        $existingIndexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='events'");
        $existingIndexNames = array_map(fn($row) => $row->name, $existingIndexes);
        
        Schema::table('events', function (Blueprint $table) use ($existingIndexNames) {
            // Composite index on (status, start_datetime) for filtering published events by date range
            if (!in_array('idx_events_status_date', $existingIndexNames)) {
                $table->index(['status', 'start_datetime'], 'idx_events_status_date');
            }
            
            // Composite index on (status, category_id) for category filtering
            if (!in_array('idx_events_status_category', $existingIndexNames)) {
                $table->index(['status', 'category_id'], 'idx_events_status_category');
            }
            
            // Index on start_datetime for date-based sorting and range queries
            if (!in_array('events_start_date_index', $existingIndexNames)) {
                $table->index('start_datetime', 'events_start_date_index');
            }
            
            // Index on status for published event filtering
            if (!in_array('events_status_index', $existingIndexNames)) {
                $table->index('status', 'events_status_index');
            }
        });
        
        // Create database view for events grouped by date with availability status
        // This view can be used by EventCalendarService for quick availability lookups
        DB::statement("DROP VIEW IF EXISTS events_by_date");
        DB::statement("
            CREATE VIEW events_by_date AS
            SELECT 
                DATE(start_date) as event_date,
                COUNT(*) as total_events,
                SUM(capacity) as total_capacity,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_events,
                SUM(CASE WHEN status = 'published' THEN capacity ELSE 0 END) as published_capacity
            FROM events
            WHERE start_datetime IS NOT NULL
            GROUP BY DATE(start_datetime)
            ORDER BY event_date
        ");
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_status_date');
            $table->dropIndex('idx_events_status_category');
            $table->dropIndex('events_start_date_index');
            $table->dropIndex('events_status_index');
        });
        
        DB::statement("DROP VIEW IF EXISTS events_by_date");
    }
};