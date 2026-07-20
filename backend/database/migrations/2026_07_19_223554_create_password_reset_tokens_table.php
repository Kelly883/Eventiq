<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('userId');
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
            $table->string('token')->index();
            $table->timestamp('expiresAt');
            $table->timestamp('usedAt')->nullable();
            $table->timestamp('createdAt')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};