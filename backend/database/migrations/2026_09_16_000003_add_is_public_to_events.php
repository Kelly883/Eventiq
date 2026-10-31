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
        if (!Schema::hasTable('events')) {
            return;
        }

        if (!Schema::hasColumn('events', 'is_public')) {
            Schema::table('events', function (Blueprint $table) {
                $table->boolean('is_public')->default(true);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('events')) {
            return;
        }

        if (Schema::hasColumn('events', 'is_public')) {
            Schema::table('events', function (Blueprint $table) {
                $table->dropColumn('is_public');
            });
        }
    }
};
