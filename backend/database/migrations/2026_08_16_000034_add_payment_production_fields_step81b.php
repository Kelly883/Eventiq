<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (! Schema::hasColumn('payments', 'payment_channel')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('payment_channel')->nullable()->after('payment_method');
            });
        }

        if (! Schema::hasColumn('payments', 'attempts')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->integer('attempts')->default(1)->after('payment_channel');
            });
        }

        if (! Schema::hasColumn('payments', 'last_error')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->text('last_error')->nullable()->after('attempts');
            });
        }

        try {
            $indexes = DB::select('PRAGMA index_list(payments)');
            $existingIndexes = [];
            foreach ($indexes as $index) {
                $existingIndexes[] = $index->name;
            }

            if (! in_array('idx_payments_user_id_status_created_at', $existingIndexes)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index(['user_id', 'status', 'created_at'], 'idx_payments_user_id_status_created_at');
                });
            }

            if (! in_array('idx_payments_gateway_status', $existingIndexes)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index(['gateway', 'status'], 'idx_payments_gateway_status');
                });
            }
        } catch (\Throwable $e) {
            // Indexes may already exist
        }

        try {
            DB::statement('CREATE TRIGGER IF NOT EXISTS trg_payments_refund_check BEFORE UPDATE ON payments FOR EACH ROW WHEN NEW.refunded_amount > OLD.amount BEGIN SELECT RAISE(ABORT, "refunded_amount cannot exceed amount"); END');
        } catch (\Throwable $e) {
            // Trigger may already exist
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        try {
            DB::statement('DROP TRIGGER IF EXISTS trg_payments_refund_check');
        } catch (\Throwable $e) {
            // Trigger may not exist
        }

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('idx_payments_user_id_status_created_at');
                $table->dropIndex('idx_payments_gateway_status');
            });
        } catch (\Throwable $e) {
            // Indexes may not exist
        }

        $columns = ['last_error', 'attempts', 'payment_channel'];

        $existing = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn('payments', $column)) {
                $existing[] = $column;
            }
        }

        if (! empty($existing)) {
            Schema::table('payments', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
