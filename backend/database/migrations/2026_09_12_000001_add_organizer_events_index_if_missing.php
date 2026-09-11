<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure organizer list query covered.
        // The list endpoint filters by (organizer_id, status?) and orders by
        // created_at DESC. A covering index on (organizer_id, status, created_at)
        // lets the DB resolve status-filtered pages from the index alone.
        $this->ensureIndex(
            'events',
            ['organizer_id', 'status', 'created_at'],
            'idx_events_organizer_status_created',
            ['organizer_id', 'status'],
        );
        $this->ensureIndex(
            'events',
            ['organizer_id', 'created_at'],
            'idx_events_organizer_created_at',
        );
        $this->ensureIndex(
            'events',
            ['organizer_id', 'start_datetime'],
            'idx_events_organizer_date',
        );
    }

    public function down(): void
    {
        // Do not drop — keep for performance
    }

    /**
     * Create an index on $table if it doesn't already exist.
     * Idempotent across SQLite, MySQL, and PostgreSQL.
     */
    private function ensureIndex(string $table, array $columns, string $name, array $prefixColumns = []): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        if ($this->indexExists($table, $name)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($columns, $name) {
            $table->index($columns, $name);
        });
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
