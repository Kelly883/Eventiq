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
                DB::statement('ALTER TABLE payouts ADD COLUMN settlement_id uuid NULL AFTER event_id');
            } catch (\Throwable $e) {
                // Column may already exist
            }
        }

        try {
            $indexes = DB::select('PRAGMA index_list(payouts)');
            $existingIndexes = [];
            foreach ($indexes as $index) {
                $existingIndexes[] = $index->name;
            }

            if (! in_array('idx_payouts_settlement_id', $existingIndexes)) {
                Schema::table('payouts', function (Blueprint $table) {
                    $table->index('settlement_id', 'idx_payouts_settlement_id');
                });
            }
        } catch (\Throwable $e) {
            // Index may already exist
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
};
