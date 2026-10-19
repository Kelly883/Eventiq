<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            try {
                DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');
            } catch (\Throwable $e) {
                // On managed PostgreSQL (Render, Heroku, RDS, etc.), the user may not have
                // permission to create extensions. The extension may already exist.
                // Log and continue rather than failing the entire migration.
                if (app()->bound('log')) {
                    \Log::warning('Could not create pgcrypto extension (may already exist or lack permissions): ' . $e->getMessage());
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            try {
                DB::statement('DROP EXTENSION IF EXISTS "pgcrypto"');
            } catch (\Throwable $e) {
                // Ignore errors on rollback (extension may be in use or lack permissions)
            }
        }
    }
};