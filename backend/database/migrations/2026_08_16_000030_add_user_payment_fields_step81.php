<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Disable the implicit transaction wrapper for this migration.
    // On managed PostgreSQL (Render, RDS, etc.), a prior statement failure
    // in the same transaction aborts the entire transaction, causing
    // subsequent CREATE TABLE/ALTER TABLE to fail with "current transaction is aborted".
    // By opting out, each statement runs in its own autocommit transaction.
    public $withinTransaction = false;
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'paystack_customer_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('paystack_customer_code')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'flutterwave_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('flutterwave_customer_id')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'default_payment_gateway')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('default_payment_gateway')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'default_payment_method_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('default_payment_method_id')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'trial_ends_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('trial_ends_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $columns = ['trial_ends_at', 'default_payment_method_id', 'default_payment_gateway', 'flutterwave_customer_id', 'paystack_customer_code'];

        $existing = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn('users', $column)) {
                $existing[] = $column;
            }
        }

        if (! empty($existing)) {
            Schema::table('users', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
