<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_methods')) {
            $hasPaymentMethodsExpiresAt = Schema::hasColumn('payment_methods', 'expires_at');
            $hasPaymentMethodsBillingName = Schema::hasColumn('payment_methods', 'billing_name');
            $hasPaymentMethodsBillingEmail = Schema::hasColumn('payment_methods', 'billing_email');
            $hasPaymentMethodsBillingPhone = Schema::hasColumn('payment_methods', 'billing_phone');
            $hasPaymentMethodsBillingAddress = Schema::hasColumn('payment_methods', 'billing_address');
            $hasPaymentMethodsBillingCity = Schema::hasColumn('payment_methods', 'billing_city');
            $hasPaymentMethodsBillingCountry = Schema::hasColumn('payment_methods', 'billing_country');
            $hasPaymentMethodsBillingZip = Schema::hasColumn('payment_methods', 'billing_zip');
            Schema::table('payment_methods', function (Blueprint $table) use ($hasPaymentMethodsExpiresAt, $hasPaymentMethodsBillingName, $hasPaymentMethodsBillingEmail, $hasPaymentMethodsBillingPhone, $hasPaymentMethodsBillingAddress, $hasPaymentMethodsBillingCity, $hasPaymentMethodsBillingCountry, $hasPaymentMethodsBillingZip) {
                if (! $hasPaymentMethodsExpiresAt) {
                    $table->dateTime('expires_at')->nullable();
                }
                if (! $hasPaymentMethodsBillingName) {
                    $table->string('billing_name')->nullable();
                }
                if (! $hasPaymentMethodsBillingEmail) {
                    $table->string('billing_email')->nullable();
                }
                if (! $hasPaymentMethodsBillingPhone) {
                    $table->string('billing_phone')->nullable();
                }
                if (! $hasPaymentMethodsBillingAddress) {
                    $table->string('billing_address')->nullable();
                }
                if (! $hasPaymentMethodsBillingCity) {
                    $table->string('billing_city')->nullable();
                }
                if (! $hasPaymentMethodsBillingCountry) {
                    $table->string('billing_country', 2)->nullable();
                }
                if (! $hasPaymentMethodsBillingZip) {
                    $table->string('billing_zip')->nullable();
                }
            });
        }

        if (Schema::hasTable('payments')) {
            if (! Schema::hasColumn('payments', 'payment_method_id')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->uuid('payment_method_id')->nullable();
                    try {
                        $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
                    } catch (\Throwable $e) {
                    }
                });
            }
        }

        if (Schema::hasTable('transactions')) {
            if (! Schema::hasColumn('transactions', 'payment_method_id')) {
                Schema::table('transactions', function (Blueprint $table) {
                    $table->uuid('payment_method_id')->nullable();
                    try {
                        $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
                    } catch (\Throwable $e) {
                    }
                });
            }
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
