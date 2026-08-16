<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        // Add missing columns
        $columnsToAdd = [
            'currency' => "varchar(3) DEFAULT 'USD' AFTER payout_method",
            'initiated_by' => 'uuid NULL AFTER approved_by',
            'payout_method_details' => 'json NULL AFTER payout_method',
        ];

        foreach ($columnsToAdd as $column => $definition) {
            if (! Schema::hasColumn('payouts', $column)) {
                try {
                    DB::statement("ALTER TABLE payouts ADD COLUMN {$column} {$definition}");
                } catch (\Throwable $e) {
                    // Column may already exist
                }
            }
        }

        // Add composite index (status, completed_at)
        try {
            $indexes = DB::select('PRAGMA index_list(payouts)');
            $hasIndex = false;
            foreach ($indexes as $index) {
                if ($index->name === 'idx_payouts_status_completed_at') {
                    $hasIndex = true;
                    break;
                }
            }
            if (! $hasIndex) {
                Schema::table('payouts', function (Blueprint $table) {
                    $table->index(['status', 'completed_at'], 'idx_payouts_status_completed_at');
                });
            }
        } catch (\Throwable $e) {
            // Index may already exist
        }

        // Add index on next_retry_at
        try {
            $indexes = DB::select('PRAGMA index_list(payouts)');
            $hasIndex = false;
            foreach ($indexes as $index) {
                if ($index->name === 'idx_payouts_next_retry_at') {
                    $hasIndex = true;
                    break;
                }
            }
            if (! $hasIndex) {
                Schema::table('payouts', function (Blueprint $table) {
                    $table->index('next_retry_at', 'idx_payouts_next_retry_at');
                });
            }
        } catch (\Throwable $e) {
            // Index may already exist
        }

        // Add check constraint on status (MySQL only, SQLite ignores)
        try {
            DB::statement("ALTER TABLE payouts ADD CONSTRAINT chk_payouts_status CHECK (status IN ('pending', 'calculated', 'approved', 'processing', 'completed', 'failed'))");
        } catch (\Throwable $e) {
            // Constraint may already exist or not supported
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
};
