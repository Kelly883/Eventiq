<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Disable the implicit transaction wrapper for this migration.
    // On managed PostgreSQL (Render, RDS, etc.), a prior statement failure
    // in the same transaction aborts the entire transaction, causing
    // subsequent CREATE TABLE/ALTER TABLE to fail with "current transaction is aborted".
    // By opting out, each statement runs in its own autocommit transaction.
    public $withinTransaction = false;
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $indexes = DB::select("SHOW INDEX FROM audit_logs WHERE Key_name = 'idx_audit_logs_fulltext'");

            if (empty($indexes)) {
                DB::statement('ALTER TABLE audit_logs ADD FULLTEXT idx_audit_logs_fulltext (action, ip_address, error_message, error_code)');
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            $indexes = DB::select("SHOW INDEX FROM audit_logs WHERE Key_name = 'idx_audit_logs_fulltext'");

            if (!empty($indexes)) {
                DB::statement('ALTER TABLE audit_logs DROP INDEX idx_audit_logs_fulltext');
            }
        }
    }
};
