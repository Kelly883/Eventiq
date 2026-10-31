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
        // NOTE: sales_start_date / sales_end_date are still actively used by TicketTier model,
        // UpdateEventRequest, mapTierData(), and availability scopes. This migration was
        // added prematurely. Keeping the columns to avoid breaking the application.
        // If the columns are ever replaced, update this migration and all call sites first.
    }

    public function down(): void
    {
        // No-op: columns were never removed.
    }
};
