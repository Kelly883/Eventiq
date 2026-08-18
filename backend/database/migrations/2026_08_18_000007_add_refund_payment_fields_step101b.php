<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('refund_requests')) {
            return;
        }

        if (! Schema::hasColumn('refund_requests', 'payment_gateway')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->string('payment_gateway')->nullable()->after('payment_gateway_response');
            });
        }

        if (! Schema::hasColumn('refund_requests', 'payment_intent_id')) {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->string('payment_intent_id')->nullable()->after('payment_gateway');
            });
        }

        try {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->index('payment_gateway', 'idx_refund_requests_payment_gateway');
                $table->index('payment_intent_id', 'idx_refund_requests_payment_intent_id');
            });
        } catch (\Throwable $e) {
            // Indexes may already exist
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('refund_requests')) {
            return;
        }

        try {
            Schema::table('refund_requests', function (Blueprint $table) {
                $table->dropIndex('idx_refund_requests_payment_gateway');
                $table->dropIndex('idx_refund_requests_payment_intent_id');
            });
        } catch (\Throwable $e) {
            // Indexes may not exist
        }

        $columns = [];
        if (Schema::hasColumn('refund_requests', 'payment_intent_id')) {
            $columns[] = 'payment_intent_id';
        }
        if (Schema::hasColumn('refund_requests', 'payment_gateway')) {
            $columns[] = 'payment_gateway';
        }

        if (! empty($columns)) {
            Schema::table('refund_requests', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
