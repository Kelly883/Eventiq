<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'paystack_customer_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('paystack_customer_code')->nullable()->after('lastLoginAt');
            });
        }

        if (! Schema::hasColumn('users', 'flutterwave_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('flutterwave_customer_id')->nullable()->after('paystack_customer_code');
            });
        }

        if (! Schema::hasColumn('users', 'default_payment_gateway')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('default_payment_gateway')->nullable()->after('flutterwave_customer_id');
            });
        }

        if (! Schema::hasColumn('users', 'default_payment_method_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('default_payment_method_id')->nullable()->after('default_payment_gateway');
            });
        }

        if (! Schema::hasColumn('users', 'trial_ends_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('trial_ends_at')->nullable()->after('default_payment_method_id');
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
