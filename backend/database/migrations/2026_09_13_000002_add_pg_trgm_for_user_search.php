<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only for PostgreSQL — pg_trgm is not available on SQLite (tests use sqlite memory)
        // The admin users/list search with LIKE '%term%' is full-scan on large tables.
        // pg_trgm GIN trigram indexes make ILIKE '%term%' use index and are ~100x faster at 100k+ users.
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        } catch (\Throwable $e) {
            // Extension may require superuser; log and skip
            \Illuminate\Support\Facades\Log::warning('pg_trgm extension not created', ['error' => $e->getMessage()]);
            return;
        }

        // GIN trigram indexes for ILIKE '%term%' — used by admin users/list search
        // Keep existing B-tree indexes for exact matches; GIN is for substring.
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_email_trgm ON users USING GIN (email gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_users_name_trgm ON users USING GIN (name gin_trgm_ops)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            DB::statement('DROP INDEX IF EXISTS idx_users_email_trgm');
            DB::statement('DROP INDEX IF EXISTS idx_users_name_trgm');
            // Do not drop extension — other features may use it
        } catch (\Throwable $e) {
        }
    }
};
