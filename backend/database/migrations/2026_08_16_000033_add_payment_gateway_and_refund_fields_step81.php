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

        if (! Schema::hasColumn('payments', 'gateway_reference')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('gateway_reference')->nullable()->after('gateway_response_code');
            });
        }

        if (! Schema::hasColumn('payments', 'refunded_amount')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('refunded_amount', 10, 2)->default(0)->after('gateway_reference');
            });
        }

        if (! Schema::hasColumn('payments', 'is_fully_refunded')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->boolean('is_fully_refunded')->default(false)->after('refunded_amount');
            });
        }

        if (! Schema::hasColumn('payments', 'user_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->uuid('user_id')->nullable()->after('order_id');
            });
        }

        try {
            $indexes = DB::select('PRAGMA index_list(payments)');
            $existingIndexes = [];
            foreach ($indexes as $index) {
                $existingIndexes[] = $index->name;
            }

            if (! in_array('idx_payments_gateway', $existingIndexes)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index('gateway', 'idx_payments_gateway');
                });
            }

            if (! in_array('idx_payments_gateway_transaction_id', $existingIndexes)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index('gateway_transaction_id', 'idx_payments_gateway_transaction_id');
                });
            }

            if (! in_array('idx_payments_gateway_reference', $existingIndexes)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index('gateway_reference', 'idx_payments_gateway_reference');
                });
            }

            if (! in_array('idx_payments_user_id', $existingIndexes)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index('user_id', 'idx_payments_user_id');
                });
            }
        } catch (\Throwable $e) {
            // Indexes may already exist
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('idx_payments_gateway');
                $table->dropIndex('idx_payments_gateway_transaction_id');
                $table->dropIndex('idx_payments_gateway_reference');
                $table->dropIndex('idx_payments_user_id');
            });
        } catch (\Throwable $e) {
            // Indexes may not exist
        }

        $columns = ['is_fully_refunded', 'refunded_amount', 'gateway_reference', 'user_id'];

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
