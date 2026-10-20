<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only run on PostgreSQL
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Check if pgcrypto extension exists using Laravel's DB facade
        // This runs in the same transaction as other migrations
        try {
            $exists = DB::selectOne(
                "SELECT 1 FROM pg_extension WHERE extname = 'pgcrypto'"
            );
            
            if (!$exists) {
                // Extension doesn't exist - try to create it
                // This may fail on managed PostgreSQL (Render, RDS, etc.) without superuser
                // In that case, we log and continue - gen_random_uuid() is built-in in PG13+
                DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');
            }
        } catch (\Throwable $e) {
            // On managed PostgreSQL, user may lack superuser to CREATE EXTENSION
            // gen_random_uuid() is built-in since PostgreSQL 13, so continue
            if (app()->bound('log')) {
                \Log::warning('Could not create/verify pgcrypto extension: ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // No-op: we don't drop extensions as they may be shared
        // On managed PostgreSQL, users typically can't DROP EXTENSION either
    }
};