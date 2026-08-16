<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        // Add source column
        if (! Schema::hasColumn('audit_logs', 'source')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->string('source')->default('web')->after('user_agent');
            });
        }

        // Add retention_reason column
        if (! Schema::hasColumn('audit_logs', 'retention_reason')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->string('retention_reason')->nullable()->after('retention_date');
            });
        }

        // Make retention_date non-nullable with default
        try {
            DB::statement('ALTER TABLE audit_logs ALTER COLUMN retention_date SET DEFAULT (datetime("now", "+7 years"))');
        } catch (\Throwable $e) {
            // SQLite may not support ALTER COLUMN SET DEFAULT
        }

        try {
            DB::statement('UPDATE audit_logs SET retention_date = datetime("now", "+7 years") WHERE retention_date IS NULL');
        } catch (\Throwable $e) {
            // Ignore if update fails
        }

        // Add composite index (target_type, target_id, created_at)
        try {
            $indexes = DB::select('PRAGMA index_list(audit_logs)');
            $hasIndex = false;
            foreach ($indexes as $index) {
                if ($index->name === 'idx_audit_logs_target_type_target_id_created_at') {
                    $hasIndex = true;
                    break;
                }
            }
            if (! $hasIndex) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    $table->index(['target_type', 'target_id', 'created_at'], 'idx_audit_logs_target_type_target_id_created_at');
                });
            }
        } catch (\Throwable $e) {
            // Index may already exist
        }

        // Add DB-level trigger to prevent user_id updates (SQLite)
        try {
            DB::statement('CREATE TRIGGER IF NOT EXISTS trg_audit_logs_prevent_user_id_update BEFORE UPDATE ON audit_logs FOR EACH ROW WHEN OLD.user_id IS NOT NULL AND NEW.user_id != OLD.user_id BEGIN SELECT RAISE(ABORT, "audit_logs.user_id is immutable"); END');
        } catch (\Throwable $e) {
            // Trigger may already exist
        }

        // Add DB-level trigger to prevent updates entirely (immutable table)
        try {
            DB::statement('CREATE TRIGGER IF NOT EXISTS trg_audit_logs_prevent_update BEFORE UPDATE ON audit_logs FOR EACH ROW BEGIN SELECT RAISE(ABORT, "audit_logs is immutable"); END');
        } catch (\Throwable $e) {
            // Trigger may already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        try {
            DB::statement('DROP TRIGGER IF EXISTS trg_audit_logs_prevent_user_id_update');
        } catch (\Throwable $e) {
            // Trigger may not exist
        }

        try {
            DB::statement('DROP TRIGGER IF EXISTS trg_audit_logs_prevent_update');
        } catch (\Throwable $e) {
            // Trigger may not exist
        }

        try {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex('idx_audit_logs_target_type_target_id_created_at');
            });
        } catch (\Throwable $e) {
            // Index may not exist
        }

        if (Schema::hasColumn('audit_logs', 'source')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }

        if (Schema::hasColumn('audit_logs', 'retention_reason')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropColumn('retention_reason');
            });
        }
    }
};
