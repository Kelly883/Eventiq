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
                $table->string('source')->default('web');
            });
        }

        // Add retention_reason column
        if (! Schema::hasColumn('audit_logs', 'retention_reason')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->string('retention_reason')->nullable();
            });
        }

        // Make retention_date non-nullable with default
        if (DB::getDriverName() === 'sqlite') {
            try {
                DB::statement('ALTER TABLE audit_logs ALTER COLUMN retention_date SET DEFAULT (datetime("now", "+7 years"))');
            } catch (\Throwable $e) {
                // SQLite may not support ALTER COLUMN SET DEFAULT
            }
        } elseif (DB::getDriverName() === 'pgsql') {
            try {
                DB::statement("ALTER TABLE audit_logs ALTER COLUMN retention_date SET DEFAULT (CURRENT_TIMESTAMP + INTERVAL '7 years')");
            } catch (\Throwable $e) {
                // PostgreSQL may not support ALTER COLUMN SET DEFAULT
            }
        }

        // Backfill existing NULL retention_date values
        if (DB::getDriverName() === 'sqlite') {
            try {
                DB::statement('UPDATE audit_logs SET retention_date = datetime("now", "+7 years") WHERE retention_date IS NULL');
            } catch (\Throwable $e) {
                // Ignore if update fails
            }
        } elseif (DB::getDriverName() === 'pgsql') {
            try {
                DB::statement("UPDATE audit_logs SET retention_date = CURRENT_TIMESTAMP + INTERVAL '7 years' WHERE retention_date IS NULL");
            } catch (\Throwable $e) {
                // Ignore if update fails
            }
        }

        // Add composite index (target_type, target_id, created_at)
        if (! $this->indexExists('audit_logs', 'idx_audit_logs_target_type_target_id_created_at')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(['target_type', 'target_id', 'created_at'], 'idx_audit_logs_target_type_target_id_created_at');
            });
        }

        // Add DB-level trigger to prevent user_id updates
        if (DB::getDriverName() === 'sqlite') {
            try {
                DB::statement('CREATE TRIGGER IF NOT EXISTS trg_audit_logs_prevent_user_id_update BEFORE UPDATE ON audit_logs FOR EACH ROW WHEN OLD.user_id IS NOT NULL AND NEW.user_id != OLD.user_id BEGIN SELECT RAISE(ABORT, "audit_logs.user_id is immutable"); END');
            } catch (\Throwable $e) {
                // Trigger may already exist
            }
        } elseif (DB::getDriverName() === 'pgsql') {
            if ($this->triggerExists('audit_logs', 'trg_audit_logs_prevent_user_id_update')) {
                DB::statement('DROP TRIGGER trg_audit_logs_prevent_user_id_update ON audit_logs');
            }
            if ($this->functionExists('trg_audit_logs_prevent_user_id_update')) {
                DB::statement('DROP FUNCTION trg_audit_logs_prevent_user_id_update()');
            }
            DB::statement('CREATE OR REPLACE FUNCTION trg_audit_logs_prevent_user_id_update() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN IF OLD.user_id IS NOT NULL AND NEW.user_id != OLD.user_id THEN RAISE EXCEPTION \'audit_logs.user_id is immutable\'; END IF; RETURN NEW; END; $$');
            DB::statement('CREATE TRIGGER trg_audit_logs_prevent_user_id_update BEFORE UPDATE OF user_id ON audit_logs FOR EACH ROW EXECUTE FUNCTION trg_audit_logs_prevent_user_id_update()');
        }

        // Add DB-level trigger to prevent updates entirely (immutable table)
        if (DB::getDriverName() === 'sqlite') {
            try {
                DB::statement('CREATE TRIGGER IF NOT EXISTS trg_audit_logs_prevent_update BEFORE UPDATE ON audit_logs FOR EACH ROW BEGIN SELECT RAISE(ABORT, "audit_logs is immutable"); END');
            } catch (\Throwable $e) {
                // Trigger may already exist
            }
        } elseif (DB::getDriverName() === 'pgsql') {
            if ($this->triggerExists('audit_logs', 'trg_audit_logs_prevent_update')) {
                DB::statement('DROP TRIGGER trg_audit_logs_prevent_update ON audit_logs');
            }
            if ($this->functionExists('trg_audit_logs_prevent_update')) {
                DB::statement('DROP FUNCTION trg_audit_logs_prevent_update()');
            }
            DB::statement('CREATE OR REPLACE FUNCTION trg_audit_logs_prevent_update() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION \'audit_logs is immutable\'; RETURN NEW; END; $$');
            DB::statement('CREATE TRIGGER trg_audit_logs_prevent_update BEFORE UPDATE ON audit_logs FOR EACH ROW EXECUTE FUNCTION trg_audit_logs_prevent_update()');
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

        if (DB::getDriverName() === 'pgsql') {
            if ($this->triggerExists('audit_logs', 'trg_audit_logs_prevent_user_id_update')) {
                DB::statement('DROP TRIGGER trg_audit_logs_prevent_user_id_update ON audit_logs');
            }
            if ($this->triggerExists('audit_logs', 'trg_audit_logs_prevent_update')) {
                DB::statement('DROP TRIGGER trg_audit_logs_prevent_update ON audit_logs');
            }
            if ($this->functionExists('trg_audit_logs_prevent_user_id_update')) {
                DB::statement('DROP FUNCTION trg_audit_logs_prevent_user_id_update()');
            }
            if ($this->functionExists('trg_audit_logs_prevent_update')) {
                DB::statement('DROP FUNCTION trg_audit_logs_prevent_update()');
            }
        } else {
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

    private function indexExists(string $table, string $index): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
                [$table, $index]
            );

            return $row !== null;
        }

        if (DB::getDriverName() === 'sqlite') {
            $row = DB::selectOne(
                'SELECT name FROM sqlite_master WHERE type = "index" AND tbl_name = ? AND name = ?',
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

    private function triggerExists(string $table, string $trigger): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT 1 FROM pg_trigger t JOIN pg_class c ON t.tgrelid = c.oid WHERE c.relname = ? AND t.tgname = ?',
                [$table, $trigger]
            );

            return $row !== null;
        }

        return false;
    }

    private function functionExists(string $function): bool
    {
        if (DB::getDriverName() === 'pgsql') {
            $row = DB::selectOne(
                'SELECT 1 FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace WHERE n.nspname = current_schema() AND p.proname = ? AND p.proargcount = 0',
                [$function]
            );

            return $row !== null;
        }

        return false;
    }
};
