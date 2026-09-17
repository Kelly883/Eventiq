<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        if (! Schema::hasColumn('tickets', 'refund_status')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->string('refund_status')->nullable()->after('status');
            });
        }

        if (! $this->indexExists('tickets', 'tickets_user_id_index')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->index('user_id', 'tickets_user_id_index');
            });
        }

        if (! $this->indexExists('tickets', 'tickets_event_id_index')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->index('event_id', 'tickets_event_id_index');
            });
        }

        if (! $this->indexExists('tickets', 'idx_tickets_event_created_at')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->index(['event_id', 'created_at'], 'idx_tickets_event_created_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tickets')) {
            return;
        }

        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropIndex('tickets_user_id_index');
            });
        } catch (\Throwable $e) {
            // Index may not exist
        }

        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropIndex('tickets_event_id_index');
            });
        } catch (\Throwable $e) {
            // Index may not exist
        }

        try {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropIndex('idx_tickets_event_created_at');
            });
        } catch (\Throwable $e) {
            // Index may not exist
        }

        if (Schema::hasColumn('tickets', 'refund_status')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropColumn('refund_status');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $row = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $indexName]
            );

            return $row !== null;
        }

        if ($driver === 'pgsql') {
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
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = current_schema() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$table, $indexName]
        );

        return $row !== null;
    }
};
