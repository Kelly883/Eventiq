<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            $this->safeAddIndex('payments', ['order_id', 'status', 'gateway'], 'payments_order_status_gateway_perf_idx');
            $this->safeAddIndex('payments', ['gateway_reference'], 'payments_gateway_reference_perf_idx');
            $this->safeAddIndex('payments', ['gateway', 'gateway_reference', 'status'], 'payments_gateway_ref_status_fraud_idx');
        }

        if (Schema::hasTable('orders')) {
            $this->safeAddIndex('orders', ['user_id', 'status', 'created_at'], 'orders_user_status_created_perf_idx');
        }

        if (Schema::hasTable('offline_sync_inbox')) {
            $this->safeAddIndex('offline_sync_inbox', ['status', 'next_retry_at', 'created_at'], 'offline_sync_due_queue_perf_idx');
            $this->safeAddIndex('offline_sync_inbox', ['client_id', 'status', 'created_at'], 'offline_sync_client_status_perf_idx');
        }
    }

    public function down(): void
    {
        $this->safeDropIndex('payments', 'payments_order_status_gateway_perf_idx');
        $this->safeDropIndex('payments', 'payments_gateway_reference_perf_idx');
        $this->safeDropIndex('payments', 'payments_gateway_ref_status_fraud_idx');

        $this->safeDropIndex('orders', 'orders_user_status_created_perf_idx');

        $this->safeDropIndex('offline_sync_inbox', 'offline_sync_due_queue_perf_idx');
        $this->safeDropIndex('offline_sync_inbox', 'offline_sync_client_status_perf_idx');
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function safeAddIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($columns, $name) {
                $table->index($columns, $name);
            });
        } catch (\Throwable) {
            // Ignore duplicate-index errors across environments with divergent migrations.
        }
    }

    private function safeDropIndex(string $table, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $table) use ($name) {
                $table->dropIndex($name);
            });
        } catch (\Throwable) {
            // Ignore missing-index errors in permissive rollback scenarios.
        }
    }
};
