<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('api_keys')) {
            return;
        }

        if (! Schema::hasColumn('api_keys', 'description')) {
            Schema::table('api_keys', function (Blueprint $table) {
                $table->text('description')->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('api_keys', 'last_used_ip')) {
            Schema::table('api_keys', function (Blueprint $table) {
                $table->string('last_used_ip')->nullable()->after('last_used_at');
            });
        }

        if (! Schema::hasColumn('api_keys', 'rate_limit')) {
            Schema::table('api_keys', function (Blueprint $table) {
                $table->integer('rate_limit')->nullable()->after('last_used_ip');
            });
        }

        if (! Schema::hasColumn('api_keys', 'rate_limit_period')) {
            Schema::table('api_keys', function (Blueprint $table) {
                $table->string('rate_limit_period')->nullable()->after('rate_limit');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('api_keys')) {
            return;
        }

        $columns = ['rate_limit_period', 'rate_limit', 'last_used_ip', 'description'];

        $existing = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn('api_keys', $column)) {
                $existing[] = $column;
            }
        }

        if (! empty($existing)) {
            Schema::table('api_keys', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
