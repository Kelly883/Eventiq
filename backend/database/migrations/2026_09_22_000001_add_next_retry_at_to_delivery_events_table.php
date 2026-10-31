<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
     * Add missing columns to delivery_events table:
     * - next_retry_at: timestamp for next retry attempt
     * - archived_at: timestamp for archiving old records
     */
    public function up(): void
    {
        Schema::table('delivery_events', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_events', 'next_retry_at')) {
                $table->timestamp('next_retry_at')->nullable()->after('last_attempt_at');
            }
            if (!Schema::hasColumn('delivery_events', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('clicked_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_events', function (Blueprint $table) {
            $table->dropColumn(['next_retry_at', 'archived_at']);
        });
    }
};
