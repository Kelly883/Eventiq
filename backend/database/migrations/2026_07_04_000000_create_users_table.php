<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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