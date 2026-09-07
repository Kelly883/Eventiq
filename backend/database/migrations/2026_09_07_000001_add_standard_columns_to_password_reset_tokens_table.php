<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The password_reset_tokens table was originally created with a bespoke
     * schema (uuid `id` / userId / token / expiresAt / usedAt / createdAt)
     * that Laravel's default password broker (DatabaseTokenRepository) never
     * wrote to. The broker inserts `email`, `token` and `created_at`, so every
     * forgot-password / reset-password request crashed with
     * "table password_reset_tokens has no column named email" and, once those
     * columns were provisionally added, "NOT NULL constraint failed:
     * password_reset_tokens.id".
     *
     * No feature uses the bespoke columns (the PasswordResetToken model and
     * User::passwordResetTokens() are orphaned leftovers), so replace the
     * table with Laravel's standard schema to make the broker work.
     */
    public function up(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('userId')->constrained('users')->cascadeOnDelete();
            $table->string('token');
            $table->timestamp('expiresAt')->nullable();
            $table->timestamp('usedAt')->nullable();
            $table->timestamp('createdAt')->useCurrent();
        });
    }
};