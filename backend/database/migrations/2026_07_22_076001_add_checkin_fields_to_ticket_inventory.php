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
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ticket_inventory', function (Blueprint $table) {
            // Add check-in tracking fields for the check-in system
            $table->integer('total_checked_in')->default(0)->after('total_available');
            $table->integer('total_void')->default(0)->after('total_checked_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_inventory', function (Blueprint $table) {
            $table->dropColumn(['total_checked_in', 'total_void']);
        });
    }
};
