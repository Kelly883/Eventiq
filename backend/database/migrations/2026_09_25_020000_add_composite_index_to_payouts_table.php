<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a composite index on payouts (organizer_id, created_at DESC).
 *
 * The payout list endpoint always scopes by organizer_id and orders/sorts by
 * created_at. On a multi-million-row table this composite index lets the DB
 * satisfy both the WHERE and ORDER BY from the index alone, avoiding a sort
 * and keeping the query fast even at scale.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payouts')) {
            return;
        }

        if ($this->indexExists('payouts', 'idx_payouts_organizer_created')) {
            return;
        }

        Schema::table('payouts', function (Blueprint $table) {
            $table->index(['organizer_id', 'created_at'], 'idx_payouts_organizer_created');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payouts')) {
            return;
        }

        if ($this->indexExists('payouts', 'idx_payouts_organizer_created')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->dropIndex('idx_payouts_organizer_created');
            });
        }
    }

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
