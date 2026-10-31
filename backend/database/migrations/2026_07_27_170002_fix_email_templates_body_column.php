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
        Schema::table('email_templates', function (Blueprint $table) {
            // Make the old 'body' column nullable since we now use 'html_body'
            $table->text('body')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            // Revert to NOT NULL - but this will fail if there's NULL data
            $table->text('body')->nullable(false)->change();
        });
    }
};