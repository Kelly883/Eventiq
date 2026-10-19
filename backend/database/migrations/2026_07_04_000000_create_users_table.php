<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw query to reliably detect existing table across schemas
        $exists = DB::select("
            SELECT 1 FROM information_schema.tables
            WHERE table_name = 'users' AND table_schema = 'public'
        ");

        if (!empty($exists)) {
            return;
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
