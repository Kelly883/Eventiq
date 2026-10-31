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
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('adminId');
            $table->uuid('user_id')->nullable()->after('event_id');
            $table->foreignId('ticket_id')->nullable()->after('user_id');
            $table->json('details')->nullable()->after('newValue');

            $table->index(['event_id', 'created_at'], 'idx_audit_event_created');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['event_id', 'user_id', 'ticket_id', 'details']);
            $table->dropIndex('idx_audit_event_created');
        });
    }
};