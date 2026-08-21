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
            'user_id' => 'uuid',
            'organizer_id' => 'uuid',
            'event_id' => 'uuid',
            'ticket_id' => 'uuid',
            'gateway_transaction_id' => 'string',
            'authorization_code' => 'string',
            'authorization_type' => 'string',
            'customer_email' => 'string',
            'customer_code' => 'string',
            'paid_at' => 'dateTime',
            'refund_reference' => 'string',
            'webhook_event_id' => 'string',
            'webhook_idempotency_key' => 'string',
        ];

        $lengths = [
            'gateway_transaction_id' => 255,
            'authorization_code' => 255,
            'authorization_type' => 100,
            'customer_email' => 255,
            'customer_code' => 255,
            'refund_reference' => 255,
            'webhook_event_id' => 255,
            'webhook_idempotency_key' => 255,
        ];

        foreach ($columnsToAdd as $column => $type) {
            if (! Schema::hasColumn('payments', $column)) {
                try {
                    Schema::table('payments', function (Blueprint $table) use ($column, $type, $lengths) {
                        if ($type === 'uuid') {
                            $table->uuid($column)->nullable();
                        } elseif ($type === 'dateTime') {
                            $table->dateTime($column)->nullable();
                        } elseif ($type === 'string' && isset($lengths[$column])) {
                            $table->string($column, $lengths[$column])->nullable();
                        } else {
                            $table->string($column)->nullable();
                        }
                    });
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

            if (Schema::hasColumn('payments', 'organizer_id') && ! in_array('idx_payments_organizer_id', $existingIndexes)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index('organizer_id', 'idx_payments_organizer_id');
                });
            }

            if (Schema::hasColumn('payments', 'event_id') && ! in_array('idx_payments_event_id', $existingIndexes)) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->index('event_id', 'idx_payments_event_id');
                });
            }

            if (Schema::hasColumn('payments', 'webhook_idempotency_key') && ! in_array('idx_payments_webhook_idempotency_key', $existingIndexes)) {
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
