<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds an index on `payouts.created_at`.
 *
 * The payout list endpoint filters by created_at (start_date/end_date). Without
 * this index every date-range scan on a multi-million-row payouts table becomes
 * a full table scan. The index is created idempotently (matching the pattern used
 * by the reconcile migration) so the migration is safe to run on already-migrated
 * production databases.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payouts')) {
            return;
        }

        if ($this->indexExists('payouts', 'payouts_created_at_index')) {
            return;
        }

        Schema::table('payouts', function (Blueprint $table) {
            $table->index('created_at', 'payouts_created_at_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payouts')) {
            return;
        }

        if ($this->indexExists('payouts', 'payouts_created_at_index')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->dropIndex('payouts_created_at_index');
            });
        }
    }

    /**
     * Check whether a named index exists on a table, cross-database.
     */
    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $index]
            ) !== null;
        }

        if ($driver === 'pgsql') {
            return DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index]
            ) !== null;
        }

        return DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
            [$table, $index]
        ) !== null;
    }
};
