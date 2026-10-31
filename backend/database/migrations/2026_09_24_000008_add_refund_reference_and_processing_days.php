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
        Schema::table('refund_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('refund_requests', 'reference_number')) {
                $table->string('reference_number', 50)->unique()->nullable()->after('expected_processing_days');
            }
            if (!Schema::hasColumn('refund_requests', 'expected_processing_days')) {
                $table->integer('expected_processing_days')->default(3)->after('refund_method');
            }
        });
    }

    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn(['reference_number', 'expected_processing_days']);
        });
    }
};
