<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        if (! Schema::hasColumn('payment_methods', 'paystack_customer_code')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('paystack_customer_code')->nullable()->after('gateway_payment_method_id');
            });
        }

        if (! Schema::hasColumn('payment_methods', 'flutterwave_customer_id')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('flutterwave_customer_id')->nullable()->after('paystack_customer_code');
            });
        }

        if (! Schema::hasColumn('payment_methods', 'brand')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('brand')->nullable()->after('type');
            });
        }

        if (! Schema::hasColumn('payment_methods', 'last_four')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('last_four')->nullable()->after('brand');
            });
        }

        if (! Schema::hasColumn('payment_methods', 'exp_month')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->integer('exp_month')->nullable()->after('last_four');
            });
        }

        if (! Schema::hasColumn('payment_methods', 'exp_year')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->integer('exp_year')->nullable()->after('exp_month');
            });
        }

        if (! Schema::hasColumn('payment_methods', 'bank_name')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('bank_name')->nullable()->after('exp_year');
            });
        }

        if (! Schema::hasColumn('payment_methods', 'account_name')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('account_name')->nullable()->after('bank_name');
            });
        }

        if (! Schema::hasColumn('payment_methods', 'account_number_last4')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('account_number_last4')->nullable()->after('account_name');
            });
        }

        try {
            $indexes = DB::select('PRAGMA index_list(payment_methods)');
            $existingIndexes = [];
            foreach ($indexes as $index) {
                $existingIndexes[] = $index->name;
            }

            if (! in_array('idx_payment_methods_user_id_gateway', $existingIndexes)) {
                Schema::table('payment_methods', function (Blueprint $table) {
                    $table->index(['user_id', 'gateway'], 'idx_payment_methods_user_id_gateway');
                });
            }
        } catch (\Throwable $e) {
            // Indexes may already exist
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        try {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropIndex('idx_payment_methods_user_id_gateway');
            });
        } catch (\Throwable $e) {
            // Index may not exist
        }

        $columns = [
            'account_number_last4',
            'account_name',
            'bank_name',
            'exp_year',
            'exp_month',
            'last_four',
            'brand',
            'flutterwave_customer_id',
            'paystack_customer_code',
        ];

        $existing = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn('payment_methods', $column)) {
                $existing[] = $column;
            }
        }

        if (! empty($existing)) {
            Schema::table('payment_methods', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
