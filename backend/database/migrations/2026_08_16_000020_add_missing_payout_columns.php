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
            $columns = DB::select('PRAGMA table_info(payouts)');
            $found = false;
            foreach ($columns as $col) {
                if ($col->name === $column) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                DB::statement('ALTER TABLE payouts ADD COLUMN ' . $column . ' ' . $definition);
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
            $columns = DB::select('PRAGMA table_info(payouts)');
            $found = false;
            foreach ($columns as $col) {
                if ($col->name === $column) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                try {
                    DB::statement('ALTER TABLE payouts DROP COLUMN ' . $column);
                } catch (\Throwable $e) {
                    // Column may not exist
                }
            }
        }
    }
};
