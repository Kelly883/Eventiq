<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isPostgres = Schema::getConnection()->getDriverName() === 'pgsql';
        Schema::create('password_reset_tokens', function (Blueprint $table) use ($isPostgres) {
            if ($isPostgres) {
                $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            } else {
                $table->uuid('id')->primary();
            }
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