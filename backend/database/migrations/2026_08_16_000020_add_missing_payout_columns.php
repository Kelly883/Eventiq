<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payouts')) {
            return;
        }

        $columnsToAdd = [
            'currency' => 'varchar(3) DEFAULT "USD"',
            'initiated_by' => 'uuid NULL',
            'payout_method_details' => 'json NULL',
        ];

        foreach ($columnsToAdd as $column => $definition) {
            if (! Schema::hasColumn('payouts', $column)) {
                try {
                    DB::statement('ALTER TABLE payouts ADD COLUMN ' . $column . ' ' . $definition);
                } catch (\Throwable $e) {
                    // Column may already exist
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('payouts')) {
            return;
        }

        foreach (['payout_method_details', 'initiated_by', 'currency'] as $column) {
            if (Schema::hasColumn('payouts', $column)) {
                try {
                    DB::statement('ALTER TABLE payouts DROP COLUMN ' . $column);
                } catch (\Throwable $e) {
                    // Column may not exist
                }
            }
        }
    }
};
