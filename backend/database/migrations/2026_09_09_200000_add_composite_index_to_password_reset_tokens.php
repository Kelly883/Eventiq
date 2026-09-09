<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance: add composite index for the hot reset-password lookup
 *  WHERE token_hash = ? AND usedAt IS NULL AND expiresAt > NOW()
 * plus a prune helper index on expiresAt. Without this the query scans
 * as the table grows (one row per forgot per user).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            // Cover the exact lookup used in AuthController::resetPassword
            $table->index(['token_hash', 'usedAt', 'expiresAt'], 'idx_prt_hash_used_expires');
            // For scheduled prune: DELETE WHERE expiresAt < NOW() - 7d
            $table->index('expiresAt', 'idx_prt_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropIndex('idx_prt_hash_used_expires');
            $table->dropIndex('idx_prt_expires_at');
        });
    }
};
