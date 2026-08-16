<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('webhooks')) {
            return;
        }

        if (! Schema::hasColumn('webhooks', 'description')) {
            Schema::table('webhooks', function (Blueprint $table) {
                $table->text('description')->nullable()->after('url');
            });
        }

        if (! Schema::hasColumn('webhooks', 'timeout_seconds')) {
            Schema::table('webhooks', function (Blueprint $table) {
                $table->integer('timeout_seconds')->default(30)->after('description');
            });
        }

        if (! Schema::hasColumn('webhooks', 'retry_policy')) {
            Schema::table('webhooks', function (Blueprint $table) {
                $table->json('retry_policy')->nullable()->after('timeout_seconds');
            });
        }

        if (! Schema::hasColumn('webhooks', 'last_success_at')) {
            Schema::table('webhooks', function (Blueprint $table) {
                $table->timestamp('last_success_at')->nullable()->after('last_failure_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('webhooks')) {
            return;
        }

        $columns = ['last_success_at', 'retry_policy', 'timeout_seconds', 'description'];

        $existing = [];
        foreach ($columns as $column) {
            if (Schema::hasColumn('webhooks', $column)) {
                $existing[] = $column;
            }
        }

        if (! empty($existing)) {
            Schema::table('webhooks', function (Blueprint $table) use ($existing) {
                $table->dropColumn($existing);
            });
        }
    }
};
