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
    public function up(): void
    {
        Schema::table('pricing_windows', function (Blueprint $table) {
            if (!Schema::hasIndex('pricing_windows', 'idx_pricing_windows_event_tier_active')) {
                $table->index(['event_id', 'ticket_category_id', 'is_active', 'deleted_at'], 'idx_pricing_windows_event_tier_active');
            }
            if (!Schema::hasIndex('pricing_windows', 'idx_pricing_windows_prioritized')) {
                $table->index(['event_id', 'priority', 'start_date_time'], 'idx_pricing_windows_prioritized');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pricing_windows', function (Blueprint $table) {
            $table->dropIndex('idx_pricing_windows_event_tier_active');
            $table->dropIndex('idx_pricing_windows_prioritized');
        });
    }
};
