<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'idempotency_key')) {
                    $table->string('idempotency_key')->unique()->nullable()->after('payment_intent_id');
                }
                if (!Schema::hasColumn('orders', 'refunded_amount')) {
                    $table->decimal('refunded_amount', 10, 2)->default(0.00)->after('total_amount');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'fee_amount')) {
                    $table->decimal('fee_amount', 10, 2)->default(0.00)->after('amount');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn(['idempotency_key', 'refunded_amount']);
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('fee_amount');
            });
        }
    }
};
