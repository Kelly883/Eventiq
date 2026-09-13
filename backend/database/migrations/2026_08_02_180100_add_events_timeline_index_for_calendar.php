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

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT i.relname FROM pg_index x '
                . 'JOIN pg_class i ON x.indexrelid = i.oid '
                . 'JOIN pg_class t ON x.indrelid = t.oid '
                . 'JOIN pg_namespace n ON t.relnamespace = n.oid '
                . 'WHERE n.nspname = current_schema() '
                . 'AND t.relname = ? '
                . 'AND i.relname = ?',
                [$table, $indexName]
            );

            return $row !== null;
        }

                $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );

        return $row !== null;
    }
};
