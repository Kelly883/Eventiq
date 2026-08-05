<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_items') || !Schema::hasTable('payments')) {
            return;
        }

        if (!$this->indexExists('order_items', 'idx_order_items_order_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->index('order_id', 'idx_order_items_order_id');
            });
        }

        if (!$this->indexExists('payments', 'idx_payments_order_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->index('order_id', 'idx_payments_order_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_items') && $this->indexExists('order_items', 'idx_order_items_order_id')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropIndex('idx_order_items_order_id');
            });
        }

        if (Schema::hasTable('payments') && $this->indexExists('payments', 'idx_payments_order_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropIndex('idx_payments_order_id');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $row = DB::selectOne(
                "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=? AND name=?",
                [$table, $indexName]
            );

            return $row !== null;
        }

        $row = DB::selectOne(
            'SELECT index_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );

        return $row !== null;
    }
};
