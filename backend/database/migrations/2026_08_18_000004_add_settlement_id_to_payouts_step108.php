<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payouts')) {
            return;
        }

        if (! Schema::hasColumn('payouts', 'settlement_id')) {
            try {
                DB::statement('ALTER TABLE payouts ADD COLUMN settlement_id uuid NULL');
            } catch (\Throwable $e) {
                // Column may already exist
            }
        }

        if (! $this->indexExists('payouts', 'idx_payouts_settlement_id')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->index('settlement_id', 'idx_payouts_settlement_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payouts')) {
            return;
        }

        try {
            Schema::table('payouts', function (Blueprint $table) {
                $table->dropIndex('idx_payouts_settlement_id');
            });
        } catch (\Throwable $e) {
            // Index may not exist
        }

        if (Schema::hasColumn('payouts', 'settlement_id')) {
            try {
                DB::statement('ALTER TABLE payouts DROP COLUMN settlement_id');
            } catch (\Throwable $e) {
                // Column may not exist
            }
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
