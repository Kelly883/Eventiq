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

        if (! Schema::hasColumn('payments', 'gateway_reference')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->string('gateway_reference')->nullable();
            });
        }

        if (! Schema::hasColumn('payments', 'refunded_amount')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('refunded_amount', 10, 2)->default(0);
            });
        }

        if (! Schema::hasColumn('payments', 'is_fully_refunded')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->boolean('is_fully_refunded')->default(false);
            });
        }

        if (! Schema::hasColumn('payments', 'user_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->uuid('user_id')->nullable();
            });
        }

        if (! $this->indexExists('payments', 'idx_payments_gateway')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('gateway', 'idx_payments_gateway');
            });
        }

        if (! $this->indexExists('payments', 'idx_payments_gateway_transaction_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('gateway_transaction_id', 'idx_payments_gateway_transaction_id');
            });
        }

        if (! $this->indexExists('payments', 'idx_payments_gateway_reference')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('gateway_reference', 'idx_payments_gateway_reference');
            });
        }

        if (! $this->indexExists('payments', 'idx_payments_user_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('user_id', 'idx_payments_user_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        try {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('idx_payments_gateway');
                $table->dropIndex('idx_payments_gateway_transaction_id');
                $table->dropIndex('idx_payments_gateway_reference');
                $table->dropIndex('idx_payments_user_id');
            });
        } catch (\Throwable $e) {
            // Indexes may not exist
        }

        $columns = ['is_fully_refunded', 'refunded_amount', 'gateway_reference', 'user_id'];

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
