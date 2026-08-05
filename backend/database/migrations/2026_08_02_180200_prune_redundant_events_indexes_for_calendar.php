<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        // Exact duplicate of idx_events_organizer_status_date.
        if ($this->indexExists('events', 'idx_events_organizer_status_start')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropIndex('idx_events_organizer_status_start');
            });
        }

        // Redundant single-column duplicate of events_start_date_index.
        if ($this->indexExists('events', 'events_start_datetime_index')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropIndex('events_start_datetime_index');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        if (!$this->indexExists('events', 'idx_events_organizer_status_start')) {
            Schema::table('events', function (Blueprint $table) {
                $table->index(['organizer_id', 'status', 'start_datetime'], 'idx_events_organizer_status_start');
            });
        }

        if (!$this->indexExists('events', 'events_start_datetime_index')) {
            Schema::table('events', function (Blueprint $table) {
                $table->index('start_datetime', 'events_start_datetime_index');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $row = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $indexName]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );

        return $row !== null;
    }
};
