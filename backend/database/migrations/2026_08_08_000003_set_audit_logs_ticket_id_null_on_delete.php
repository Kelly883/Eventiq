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
        if (! Schema::hasColumn('audit_logs', 'ticket_id')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE audit_logs DROP FOREIGN KEY audit_logs_ticket_id_foreign');
            DB::statement('ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('audit_logs', 'ticket_id')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE audit_logs DROP FOREIGN KEY audit_logs_ticket_id_foreign');
            DB::statement('ALTER TABLE audit_logs ADD CONSTRAINT audit_logs_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE');
        }
    }
};
