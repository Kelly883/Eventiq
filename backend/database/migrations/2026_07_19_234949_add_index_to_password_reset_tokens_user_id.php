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
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->index('userId');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->index('userId');
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropIndex(['userId']);
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropIndex(['userId']);
        });
    }
};