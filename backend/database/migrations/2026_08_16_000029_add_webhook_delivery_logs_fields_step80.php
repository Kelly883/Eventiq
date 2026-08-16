<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('webhook_delivery_logs')) {
            return;
        }

        if (! Schema::hasColumn('webhook_delivery_logs', 'attempt_number')) {
            Schema::table('webhook_delivery_logs', function (Blueprint $table) {
                $table->integer('attempt_number')->default(1)->after('event');
            });
        }

        if (! Schema::hasColumn('webhook_delivery_logs', 'error_message')) {
            Schema::table('webhook_delivery_logs', function (Blueprint $table) {
                $table->text('error_message')->nullable()->after('response_body');
            });
        }

        try {
            $indexes = DB::select('PRAGMA index_list(webhook_delivery_logs)');
            $hasIndex = false;
            foreach ($indexes as $index) {
                if ($index->name === 'idx_webhook_delivery_logs_webhook_id_created_at') {
                    $hasIndex = true;
                    break;
                }
            }

            if (! $hasIndex) {
                Schema::table('webhook_delivery_logs', function (Blueprint $table) {
                    $table->index(['webhook_id', 'created_at'], 'idx_webhook_delivery_logs_webhook_id_created_at');
                });
            }
        } catch (\Throwable $e) {
            // Index may already exist
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('webhook_delivery_logs')) {
            return;
        }

        try {
            Schema::table('webhook_delivery_logs', function (Blueprint $table) {
                $table->dropIndex('idx_webhook_delivery_logs_webhook_id_created_at');
            });
        } catch (\Throwable $e) {
            // Index may not exist
        }

        $columns = ['error_message', 'attempt_number'];

        $existing = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn('webhook_delivery_logs', $column)) {
                $existing[] = $column;
            }
        }

        if (! empty($existing)) {
            Schema::table('webhook_delivery_logs', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
