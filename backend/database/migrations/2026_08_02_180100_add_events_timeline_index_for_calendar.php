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

        if ($this->indexExists('events', 'idx_events_status_start_id')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            // Supports stable status timeline listings with sort + pagination.
            $table->index(['status', 'start_datetime', 'id'], 'idx_events_status_start_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        if (!$this->indexExists('events', 'idx_events_status_start_id')) {
            return;
        }

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_status_start_id');
        });
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
