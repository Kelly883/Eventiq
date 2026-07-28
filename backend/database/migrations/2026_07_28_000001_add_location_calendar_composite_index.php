<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        $existingIndexNames = [];
        if (DB::getDriverName() === 'sqlite') {
            $existingIndexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='events'");
            $existingIndexNames = array_map(fn($row) => $row->name, $existingIndexes);
        } else {
            $existingIndexes = DB::select(
                'SELECT index_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ?',
                ['events']
            );
            $existingIndexNames = array_map(fn($row) => $row->index_name ?? $row->INDEX_NAME ?? '', $existingIndexes);
        }

        Schema::table('events', function (Blueprint $table) use ($existingIndexNames) {
            // Composite index on (status, venue_address, start_datetime) for location/venue-filtered calendar queries
            if (!in_array('idx_events_status_venue_date', $existingIndexNames)) {
                $table->index(['status', 'venue_address', 'start_datetime'], 'idx_events_status_venue_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_status_venue_date');
        });
    }
};
