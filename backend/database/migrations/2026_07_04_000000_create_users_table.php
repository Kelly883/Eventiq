<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw PDO to check table existence and primary key, avoiding transaction issues
        $pdo = DB::connection()->getPdo();
        
        // Check if users table exists
        $stmt = $pdo->query("
            SELECT 1 FROM information_schema.tables
            WHERE table_name = 'users' AND table_schema = 'public'
            LIMIT 1
        ");
        $tableExists = $stmt && $stmt->fetch();
        
        if ($tableExists) {
            // Check if primary key exists
            $stmt = $pdo->query("
                SELECT 1 FROM information_schema.table_constraints
                WHERE table_name = 'users' AND table_schema = 'public'
                AND constraint_type = 'PRIMARY KEY'
                LIMIT 1
            ");
            $hasPrimaryKey = $stmt && $stmt->fetch();
            
            if ($hasPrimaryKey) {
                // Table exists and has primary key - migration already applied
                return;
            }
            
            // Table exists but is incomplete (no primary key) - drop and recreate
            Schema::dropIfExists('users');
        }

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
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
