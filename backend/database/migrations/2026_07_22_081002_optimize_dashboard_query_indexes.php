<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('events')
            && Schema::hasColumn('events', 'organizer_id')
            && Schema::hasColumn('events', 'status')
            && Schema::hasColumn('events', 'start_datetime')
            && !$this->indexExists('events', 'idx_events_organizer_status_start')) {
            Schema::table('events', function (Blueprint $table) {
                $table->index(['organizer_id', 'status', 'start_datetime'], 'idx_events_organizer_status_start');
            });
        }

        if (Schema::hasTable('analytics_events_metrics')
            && Schema::hasColumn('analytics_events_metrics', 'organizer_id')
            && Schema::hasColumn('analytics_events_metrics', 'last_updated_at')
            && !$this->indexExists('analytics_events_metrics', 'idx_metrics_organizer_updated')) {
            Schema::table('analytics_events_metrics', function (Blueprint $table) {
                $table->index(['organizer_id', 'last_updated_at'], 'idx_metrics_organizer_updated');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('events', 'idx_events_organizer_status_start')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropIndex('idx_events_organizer_status_start');
            });
        }

        if ($this->indexExists('analytics_events_metrics', 'idx_metrics_organizer_updated')) {
            Schema::table('analytics_events_metrics', function (Blueprint $table) {
                $table->dropIndex('idx_metrics_organizer_updated');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
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
