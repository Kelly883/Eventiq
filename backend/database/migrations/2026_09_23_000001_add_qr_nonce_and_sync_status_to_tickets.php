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
        // Add qr_nonce for replay attack prevention
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'qr_nonce')) {
                $table->string('qr_nonce', 64)->nullable()->after('qr_code_expires_at');
                $table->index('qr_nonce');
            }
        });

        // Add sync_status for offline sync deduplication
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'sync_status')) {
                $table->enum('sync_status', ['pending', 'synced', 'conflict'])->default('pending')->after('qr_nonce');
                $table->index('sync_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'qr_nonce')) {
                $table->dropColumn('qr_nonce');
            }
            if (Schema::hasColumn('tickets', 'sync_status')) {
                $table->dropColumn('sync_status');
            }
        });
    }
};
