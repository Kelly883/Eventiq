<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a deterministic SHA-256 lookup hash (`token_hash`) alongside the bcrypt
 * `token`. The bcrypt hash is non-deterministic and cannot be queried directly,
 * so the previous reset-password flow scanned every active token with
 * Hash::check (O(n)). With this column we can look up the token in O(1) and
 * still verify with Hash::check before trusting it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->string('token_hash')->nullable()->after('token')->index();
        });

        // Backfill: derive sha256 of the existing bcrypt tokens is not possible
        // (one-way), so leave nullable. New tokens will populate this column.
    }

    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropColumn('token_hash');
        });
    }
};
