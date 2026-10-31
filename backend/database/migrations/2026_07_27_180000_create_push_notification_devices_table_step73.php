<?php

use Illuminate\Database\Migrations\Migration;

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
		// Intentionally left blank: schema already covered by prior migrations.
	}

	public function down(): void
	{
		// No-op.
	}
};
