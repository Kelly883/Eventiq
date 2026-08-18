<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        $columnsToAdd = [
            'user_id' => 'uuid NULL AFTER order_id',
            'organizer_id' => 'uuid NULL AFTER user_id',
            'event_id' => 'uuid NULL AFTER organizer_id',
            'ticket_id' => 'uuid NULL AFTER event_id',
            'gateway_transaction_id' => 'varchar(255) NULL AFTER gateway_reference',
            'authorization_code' => 'varchar(255) NULL AFTER gateway_transaction_id',
            'authorization_type' => 'varchar(100) NULL AFTER authorization_code',
            'customer_email' => 'varchar(255) NULL AFTER payment_channel',
            'customer_code' => 'varchar(255) NULL AFTER customer_email',
            'paid_at' => 'datetime NULL AFTER last_error',
            'refund_reference' => 'varchar(255) NULL AFTER refund_reason',
            'webhook_event_id' => 'varchar(255) NULL AFTER webhook_idempotency_key',
            'webhook_idempotency_key' => 'varchar(255) NULL AFTER attempts',
        ];

        foreach ($columnsToAdd as $column => $definition) {
            if (! Schema::hasColumn('payments', $column)) {
                try {
                    DB::statement("ALTER TABLE payments ADD COLUMN {$column} {$definition}");
                } catch (\Throwable $e) {
                    // Column may already exist
                }
            }
        }

        try {
            $indexes = DB::select('PRAGMA index_list(payments)');
            $existingIndexes = [];
            foreach ($indexes as $index) {
                $existingIndexes[] = $index->name;
            }

            if (! in_array('idx_payments_organizer_id', $existingIndexes)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index('organizer_id', 'idx_payments_organizer_id');
                });
            }

            if (! in_array('idx_payments_event_id', $existingIndexes)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index('event_id', 'idx_payments_event_id');
                });
            }

            if (! in_array('idx_payments_webhook_idempotency_key', $existingIndexes)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->unique('webhook_idempotency_key', 'idx_payments_webhook_idempotency_key');
                });
            }
        } catch (\Throwable $e) {
            // Indexes may already exist
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('idx_payments_organizer_id');
                $table->dropIndex('idx_payments_event_id');
                $table->dropUnique('idx_payments_webhook_idempotency_key');
            });
        } catch (\Throwable $e) {
            // Indexes may not exist
        }

        $columns = [
            'webhook_event_id',
            'webhook_idempotency_key',
            'refund_reference',
            'paid_at',
            'customer_code',
            'customer_email',
            'authorization_type',
            'authorization_code',
            'gateway_transaction_id',
            'ticket_id',
            'event_id',
            'organizer_id',
            'user_id',
        ];

        $existing = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn('payments', $column)) {
                $existing[] = $column;
            }
        }

        if (! empty($existing)) {
            Schema::table('payments', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
