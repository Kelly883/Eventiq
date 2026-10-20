<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use Laravel's Schema facade consistently - it works within transactions
        // and handles all database drivers properly
        
        if (Schema::hasTable('users')) {
            // Check if table has primary key using Laravel's schema builder
            $columns = Schema::getColumnListing('users');
            
            // If table exists and has columns, check if it has primary key
            // For a properly created users table, it should have 'id' as primary
            if (in_array('id', $columns)) {
                // Table exists with id column - assume properly migrated
                return;
            }
            
            // Table exists but incomplete - drop and recreate
            Schema::dropIfExists('users');
        }

        // Create the table fresh
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