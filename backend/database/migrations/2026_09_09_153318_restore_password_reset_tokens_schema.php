<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The password_reset_tokens table was previously replaced with Laravel's
 * minimal broker schema (email/token/created_at), which lacks the userId,
 * expiresAt and usedAt columns the token-based reset flow requires. Drop and
 * recreate it with the bespoke schema so hashed tokens can be looked up and
 * expired / used state tracked per user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('userId')->constrained('users')->cascadeOnDelete();
            $table->string('token')->index();
            $table->timestamp('expiresAt')->nullable();
            $table->timestamp('usedAt')->nullable();
            $table->timestamp('createdAt')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
