<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('push_notification_devices') && ! Schema::hasColumn('push_notification_devices', 'last_used_at')) {
            DB::statement('ALTER TABLE push_notification_devices ADD COLUMN last_used_at TIMESTAMP NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('push_notification_devices') && Schema::hasColumn('push_notification_devices', 'last_used_at')) {
            DB::statement('ALTER TABLE push_notification_devices DROP COLUMN last_used_at');
        }
    }
};
