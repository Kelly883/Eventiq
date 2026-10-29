<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Disable the implicit transaction wrapper for this migration.
    // The introspection queries can fail on managed PG (permission denied on
    // pg_catalog.pg_attribute), which aborts the outer transaction and causes
    // the subsequent CREATE TABLE to fail with "current transaction is aborted".
    // By opting out, each statement runs in its own autocommit transaction.
    public $withinTransaction = false;

    public function up(): void
    {
        // Check if table exists using raw SQL to avoid permission issues on managed PostgreSQL
        // that cause transaction aborts when querying information_schema
        $tableExists = false;
        $hasIdColumn = false;

        try {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'pgsql') {
                // Use pg_catalog which is more permissive on managed PG instances
                $result = DB::select("
                    SELECT 1 FROM pg_catalog.pg_tables
                    WHERE schemaname = current_schema() AND tablename = 'users'
                ");
                $tableExists = !empty($result);

                if ($tableExists) {
                    $columns = DB::select("
                        SELECT column_name FROM pg_catalog.pg_attribute
                        WHERE attrelid = 'users'::regclass AND attnum > 0 AND NOT attisdropped
                    ");
                    $hasIdColumn = collect($columns)->contains('column_name', 'id');
                }
            } else {
                // SQLite/MySQL - use Schema facade (less permission issues)
                $tableExists = Schema::hasTable('users');
                if ($tableExists) {
                    $columns = Schema::getColumnListing('users');
                    $hasIdColumn = in_array('id', $columns);
                }
            }
        } catch (\Throwable $e) {
            // If introspection fails (e.g., permissions on managed PG), assume fresh DB
            $tableExists = false;
            $hasIdColumn = false;
        }

        if ($tableExists && $hasIdColumn) {
            // Table exists with id column - assume properly migrated
            return;
        }

        if ($tableExists && !$hasIdColumn) {
            // Table exists but incomplete - drop and recreate
            Schema::dropIfExists('users');
        }

        // Create the table fresh
        $isPostgres = Schema::getConnection()->getDriverName() === 'pgsql';
        Schema::create('users', function (Blueprint $table) use ($isPostgres) {
            if ($isPostgres) {
                $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            } else {
                $table->uuid('id')->primary();
            }
            $table->string('name');
            $table->string('email')->unique()->index();
            $table->string('passwordHash');
            $table->string('role')->default('attendee');
            $table->boolean('emailVerified')->default(false);
            $table->timestamps();
            $table->timestamp('lastLoginAt')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};