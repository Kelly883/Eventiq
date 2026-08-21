<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_methods')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                if (! Schema::hasColumn('payment_methods', 'expires_at')) {
                    $table->dateTime('expires_at')->nullable()->after('exp_year');
                }
                if (! Schema::hasColumn('payment_methods', 'billing_name')) {
                    $table->string('billing_name')->nullable()->after('account_name');
                }
                if (! Schema::hasColumn('payment_methods', 'billing_email')) {
                    $table->string('billing_email')->nullable()->after('billing_name');
                }
                if (! Schema::hasColumn('payment_methods', 'billing_phone')) {
                    $table->string('billing_phone')->nullable()->after('billing_email');
                }
                if (! Schema::hasColumn('payment_methods', 'billing_address')) {
                    $table->string('billing_address')->nullable()->after('billing_phone');
                }
                if (! Schema::hasColumn('payment_methods', 'billing_city')) {
                    $table->string('billing_city')->nullable()->after('billing_address');
                }
                if (! Schema::hasColumn('payment_methods', 'billing_country')) {
                    $table->string('billing_country', 2)->nullable()->after('billing_city');
                }
                if (! Schema::hasColumn('payment_methods', 'billing_zip')) {
                    $table->string('billing_zip')->nullable()->after('billing_country');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->uuid('payment_method_id')->nullable()->after('payment_intent_id');
                try {
                    $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
                } catch (\Throwable $e) {
                }
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->uuid('payment_method_id')->nullable()->after('gateway_reference');
                try {
                    $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
                } catch (\Throwable $e) {
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['payment_method_id']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('payment_method_id');
            });
        }

        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                try {
                    $table->dropForeign(['payment_method_id']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('payment_method_id');
            });
        }

        if (Schema::hasTable('payment_methods')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropColumn([
                    'expires_at',
                    'billing_name',
                    'billing_email',
                    'billing_phone',
                    'billing_address',
                    'billing_city',
                    'billing_country',
                    'billing_zip',
                ]);
            });
        }
    }
};
