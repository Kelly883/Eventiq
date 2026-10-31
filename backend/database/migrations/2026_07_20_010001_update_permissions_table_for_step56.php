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
        Schema::table('permissions', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->string('category')->nullable()->after('description');
            $table->string('riskLevel')->nullable()->after('category');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'riskLevel']);
            $table->string('description')->nullable()->change();
        });
    }
};