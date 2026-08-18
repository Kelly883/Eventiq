<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        if (! Schema::hasColumn('audit_logs', 'ip_address')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->string('ip_address')->nullable()->after('user_id');
            });
        }

        try {
            $indexes = DB::select('PRAGMA index_list(audit_logs)');
            $hasIndex = false;
            foreach ($indexes as $index) {
                if ($index->name === 'idx_audit_logs_user_id_action_created_at') {
                    $hasIndex = true;
                    break;
                }
            }

            if (! $hasIndex) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index(['user_id', 'action', 'created_at'], 'idx_audit_logs_user_id_action_created_at');
                });
            }
        } catch (\Throwable $e) {
            // Index may already exist
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        try {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('idx_audit_logs_user_id_action_created_at');
            });
        } catch (\Throwable $e) {
            // Index may not exist
        }

        if (Schema::hasColumn('audit_logs', 'ip_address')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn('ip_address');
            });
        }
    }
};
