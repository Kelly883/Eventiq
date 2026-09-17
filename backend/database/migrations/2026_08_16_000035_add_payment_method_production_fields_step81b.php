<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        if (! Schema::hasColumn('payment_methods', 'last_four')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('last_four')->nullable();
            });
        }

        if (! Schema::hasColumn('payment_methods', 'expires_at')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable();
            });
        }

        if (! $this->indexExists('payment_methods', 'idx_payment_methods_user_id_is_default')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->index(['user_id', 'is_default'], 'idx_payment_methods_user_id_is_default');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        try {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropIndex('idx_payment_methods_user_id_is_default');
            });
        } catch (\Throwable $e) {
            // Index may not exist
        }

        $columns = ['expires_at', 'last_four'];

        $existing = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn('payment_methods', $column)) {
                $existing[] = $column;
            }
        }

        if (! empty($existing)) {
            Schema::table('payment_methods', function (Blueprint $table) use ($existing) {
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
