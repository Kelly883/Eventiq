<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * On managed PostgreSQL (Render, Heroku, RDS, etc.), the database user
     * typically lacks superuser privileges required to CREATE EXTENSION.
     * Instead of attempting to create the extension and catching the error
     * (which leaves the transaction in an aborted state), we check if the
     * extension already exists. If it does, we skip. If it doesn't, we log
     * a warning and continue - the migration will fail later with a clear
     * error if UUID generation is actually needed but pgcrypto is missing.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Check if pgcrypto extension already exists
        $exists = DB::selectOne(
            "SELECT 1 FROM pg_extension WHERE extname = 'pgcrypto'"
        );

        if ($exists) {
            return; // Extension already available
        }

        // Extension doesn't exist and we can't create it (no superuser).
        // Log a warning but don't fail - gen_random_uuid() may still work
        // if the extension was created by a superuser separately, or if
        // PostgreSQL 13+ has built-in gen_random_uuid().
        if (app()->bound('log')) {
            \Log::warning('pgcrypto extension not found and cannot be created (requires superuser). UUID generation may fail if gen_random_uuid() is not available.');
        }
    }

    public function down(): void
    {
        // No-op: we never create the extension, so nothing to roll back.
        // On managed PostgreSQL, users typically can't DROP EXTENSION either.
    }
};