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
        Schema::create('audit_log_tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('audit_log_id');
            $table->string('tag');
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at');

            $table->unique(['audit_log_id', 'tag'], 'uniq_audit_log_tag');
            $table->index(['audit_log_id', 'tag']);
            $table->index('tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log_tags');
    }
};
