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
                $table->string('paystack_customer_code')->nullable();
            });
        }

        if (! Schema::hasColumn('payment_methods', 'flutterwave_customer_id')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('flutterwave_customer_id')->nullable();
            });
        }

        if (! Schema::hasColumn('payment_methods', 'brand')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('brand')->nullable();
            });
        }

        if (! Schema::hasColumn('payment_methods', 'last_four')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('last_four')->nullable();
            });
        }

        if (! Schema::hasColumn('payment_methods', 'exp_month')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->integer('exp_month')->nullable();
            });
        }

        if (! Schema::hasColumn('payment_methods', 'exp_year')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->integer('exp_year')->nullable();
            });
        }

        if (! Schema::hasColumn('payment_methods', 'bank_name')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('bank_name')->nullable();
            });
        }

        if (! Schema::hasColumn('payment_methods', 'account_name')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('account_name')->nullable();
            });
        }

        if (! Schema::hasColumn('payment_methods', 'account_number_last4')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('account_number_last4')->nullable();
            });
        }

        if (! $this->indexExists('payment_methods', 'idx_payment_methods_user_id_gateway')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->index(['user_id', 'gateway'], 'idx_payment_methods_user_id_gateway');
            });
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

    private function indexExists(string $table, string $index): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            $row = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $index]
            );

            return $row !== null;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return $row !== null;
    }
};
