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
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasIndex('payments', 'payments_refund_reference_unique')) {
                $table->unique('refund_reference', 'payments_refund_reference_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasIndex('payments', 'payments_refund_reference_unique')) {
                $table->dropUnique('payments_refund_reference_unique');
            }
        });
    }
};
