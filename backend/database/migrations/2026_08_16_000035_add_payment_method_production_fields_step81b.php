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
                $table->string('last_four')->nullable()->after('type');
            });
        }

        if (! Schema::hasColumn('payment_methods', 'expires_at')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('last_four');
            });
        }

        try {
            $indexes = DB::select('PRAGMA index_list(payment_methods)');
            $existingIndexes = [];
            foreach ($indexes as $index) {
                $existingIndexes[] = $index->name;
            }

            if (! in_array('idx_payment_methods_user_id_is_default', $existingIndexes)) {
                Schema::table('payment_methods', function (Blueprint $table) {
                    $table->index(['user_id', 'is_default'], 'idx_payment_methods_user_id_is_default');
                });
            }
        } catch (\Throwable $e) {
            // Indexes may already exist
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
};
