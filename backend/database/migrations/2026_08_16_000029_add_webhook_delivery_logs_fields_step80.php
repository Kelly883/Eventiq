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
                $table->integer('attempt_number')->default(1);
            });
        }

        if (! Schema::hasColumn('webhook_delivery_logs', 'error_message')) {
            Schema::table('webhook_delivery_logs', function (Blueprint $table) {
                $table->text('error_message')->nullable();
            });
        }

        if (! $this->indexExists('webhook_delivery_logs', 'idx_webhook_delivery_logs_webhook_id_created_at')) {
            Schema::table('webhook_delivery_logs', function (Blueprint $table) {
                $table->index(['webhook_id', 'created_at'], 'idx_webhook_delivery_logs_webhook_id_created_at');
            });
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

    private function indexExists(string $table, string $index): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'sqlite') {
            $row = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = ? AND name = ?",
                [$table, $index]
            );

            return $row !== null;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = current_schema() AND table_name = ? AND index_name = ?',
            [$table, $index]
        );

        return $row !== null;
    }
};
