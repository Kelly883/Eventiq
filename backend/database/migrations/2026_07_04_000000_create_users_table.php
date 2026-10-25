<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use Laravel's Schema facade consistently - it works within transactions
        // and handles all database drivers properly
        
        // Check if table exists - wrap in try-catch to avoid transaction abort on permission issues
        $tableExists = false;
        $hasIdColumn = false;
        try {
            $tableExists = Schema::hasTable('users');
            if ($tableExists) {
                $columns = Schema::getColumnListing('users');
                $hasIdColumn = in_array('id', $columns);
            }
        } catch (\Throwable $e) {
            // If schema introspection fails (e.g., permission issues on managed PG),
            // proceed to create table - it will fail with a clear error if table exists
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