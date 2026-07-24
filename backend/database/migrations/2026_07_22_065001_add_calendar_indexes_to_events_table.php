<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes for fast calendar queries on events table.
     *
     * FIXES applied in this version:
     * 1. Changed (status, category_id) to (status, category) since the column is string 'category', not 'category_id'
     * 2. Fixed view: DATE(start_date) -> DATE(start_datetime) to match actual column name
     * 3. Added (organizer_id, start_datetime) composite index for organizer calendar queries
     * 4. Added (category, status, start_datetime) composite index for category+date filtering
     * 5. Added (event_id, total_available) index on ticket_inventory for availability queries
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
            
            // Composite index on (status, category) for category filtering (category is a string column, not category_id)
            if (!in_array('idx_events_status_category', $existingIndexNames)) {
                $table->index(['status', 'category'], 'idx_events_status_category');
            }
            
            // Composite index on (organizer_id, start_datetime) for organizer calendar queries
            if (!in_array('idx_events_organizer_date', $existingIndexNames)) {
                $table->index(['organizer_id', 'start_datetime'], 'idx_events_organizer_date');
            }
            
            // Composite index on (category, status, start_datetime) for category + date filtering
            if (!in_array('idx_events_category_status_date', $existingIndexNames)) {
                $table->index(['category', 'status', 'start_datetime'], 'idx_events_category_status_date');
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
        
        // Add index on ticket_inventory for availability queries (event_id, total_available)
        if (Schema::hasTable('ticket_inventory')) {
            $existingInvIndexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='ticket_inventory'");
            $existingInvNames = array_map(fn($row) => $row->name, $existingInvIndexes);
            
            Schema::table('ticket_inventory', function (Blueprint $table) use ($existingInvNames) {
                if (!in_array('inv_event_available_idx', $existingInvNames)) {
                    $table->index(['event_id', 'total_available'], 'inv_event_available_idx');
                }
            });
        }
        
        // Create database view for events grouped by date with availability status
        // This view can be used by EventCalendarService for quick availability lookups
        DB::statement("DROP VIEW IF EXISTS events_by_date");
        DB::statement("
            CREATE VIEW events_by_date AS
            SELECT 
                DATE(start_datetime) as event_date,
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
            $table->dropIndex('idx_events_organizer_date');
            $table->dropIndex('idx_events_category_status_date');
            $table->dropIndex('events_start_date_index');
            $table->dropIndex('events_status_index');
        });
        
        // Drop the ticket_inventory index if the table exists
        if (Schema::hasTable('ticket_inventory')) {
            Schema::table('ticket_inventory', function (Blueprint $table) {
                $table->dropIndex('inv_event_available_idx');
            });
        }
        
        DB::statement("DROP VIEW IF EXISTS events_by_date");
    }
};

