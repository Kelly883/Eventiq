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
        // Note: The composite index (organizer_id, status) is already created
        // by migration 2026_07_20_010009_update_events_table_for_step58.
        // This migration originally tried to create it again (causing a crash).
        // We only need to handle the ticket_tiers.capacity drop here.

        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->dropColumn('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_tiers', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->nullable()->after('price');
        });
    }
};