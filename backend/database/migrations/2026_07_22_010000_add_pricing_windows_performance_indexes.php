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
     * Add performance indexes for pricing_windows:
     * 1. idx_windows_tier_active_daterange — covers queries filtering by ticket_category_id + is_active + date range
     * 2. idx_windows_event_priority_start — covers sorting by event_id + priority + start_date_time (for scopePrioritized)
     */
    public function up(): void
    {
        Schema::table('pricing_windows', function (Blueprint $table) {
            $table->index(
                ['ticket_category_id', 'is_active', 'start_date_time', 'end_date_time'],
                'idx_windows_tier_active_daterange'
            );

            $table->index(
                ['event_id', 'priority', 'start_date_time'],
                'idx_windows_event_priority_start'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pricing_windows', function (Blueprint $table) {
            $table->dropIndex('idx_windows_tier_active_daterange');
            $table->dropIndex('idx_windows_event_priority_start');
        });
    }
};

