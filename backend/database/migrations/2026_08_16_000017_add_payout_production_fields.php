<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payouts')) {
            return;
        }

        $driver = DB::getDriverName();

        $columnsToAdd = [
            'currency' => "varchar(3) DEFAULT 'USD' AFTER payout_method",
            'initiated_by' => 'uuid NULL AFTER approved_by',
            'payout_method_details' => 'json NULL AFTER payout_method',
        ];

        foreach ($columnsToAdd as $column => $definition) {
            if (! Schema::hasColumn('payouts', $column)) {
                try {
                    $columnDef = $driver === 'mysql' ? $definition : preg_replace('/\s+AFTER\s+\w+/', '', $definition);
                    DB::statement("ALTER TABLE payouts ADD COLUMN {$column} {$columnDef}");
                } catch (\Throwable $e) {
                    // Column may already exist
                }
            }
        }

        if (! $this->indexExists('payouts', 'idx_payouts_status_completed_at')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->index(['status', 'completed_at'], 'idx_payouts_status_completed_at');
            });
        }

        if (! $this->indexExists('payouts', 'idx_payouts_next_retry_at')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->index('next_retry_at', 'idx_payouts_next_retry_at');
            });
        }

        try {
            DB::statement("ALTER TABLE payouts ADD CONSTRAINT chk_payouts_status CHECK (status IN ('pending', 'calculated', 'approved', 'processing', 'completed', 'failed'))");
        } catch (\Throwable $e) {
            // Constraint may already exist or not supported
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payouts')) {
            return;
        }

        try {
            Schema::table('payouts', function (Blueprint $table) {
                $table->dropIndex('idx_payouts_status_completed_at');
            });
        } catch (\Throwable $e) {
            // Index may not exist
        }

        try {
            Schema::table('payouts', function (Blueprint $table) {
                $table->dropIndex('idx_payouts_next_retry_at');
            });
        } catch (\Throwable $e) {
            // Index may not exist
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
