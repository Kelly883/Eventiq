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
        if (Schema::hasTable('api_keys') && ! Schema::hasColumn('api_keys', 'key_hash_index')) {
            Schema::table('api_keys', function (Blueprint $table) {
                $table->string('key_hash_index', 64)->nullable()->after('hashed_key');
                $table->index('key_hash_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('api_keys') && Schema::hasColumn('api_keys', 'key_hash_index')) {
            Schema::table('api_keys', function (Blueprint $table) {
                $table->dropIndex(['key_hash_index']);
                $table->dropColumn('key_hash_index');
            });
        }
    }
};
