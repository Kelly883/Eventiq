<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add webhook_event_id to payments if missing, then add unique
        // constraint to prevent duplicate webhook processing (dedup at the
        // DB level backs up the application-level check).
        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'webhook_event_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('webhook_event_id')->nullable()->after('idempotency_key');
            });
        }

        if (Schema::hasTable('payments') && ! Schema::hasIndex('payments', 'payments_webhook_event_id_unique')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unique('webhook_event_id', 'payments_webhook_event_id_unique');
            });
        }

        // Add idempotency_key to orders if missing (no earlier migration
        // creates it on orders - only payments got one), then add unique
        // constraint to prevent duplicate order creation on checkout retry.
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'idempotency_key')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('idempotency_key')->nullable()->after('payment_intent_id');
            });
        }

        if (Schema::hasTable('orders') && ! Schema::hasIndex('orders', 'orders_idempotency_key_unique')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique('idempotency_key', 'orders_idempotency_key_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('payments', 'payments_webhook_event_id_unique')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropUnique('payments_webhook_event_id_unique');
            });
        }

        if (Schema::hasIndex('orders', 'orders_idempotency_key_unique')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_idempotency_key_unique');
            });
        }
    }
};
