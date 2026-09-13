<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasUsersPasswordChangedAt = Schema::hasColumn('users', 'password_changed_at');
        Schema::table('users', function (Blueprint $table) use ($hasUsersPasswordChangedAt) {
            if (!$hasUsersPasswordChangedAt) {
                $table->timestamp('password_changed_at')->nullable()->after('passwordHash');
                $table->index('password_changed_at');
            }
        });
    }

    public function down(): void
    {
        $hasUsersPasswordChangedAt = Schema::hasColumn('users', 'password_changed_at');
        Schema::table('users', function (Blueprint $table) use ($hasUsersPasswordChangedAt) {
            if ($hasUsersPasswordChangedAt) {
                $table->dropIndex(['password_changed_at']);
                $table->dropColumn('password_changed_at');
            }
        });
    }
};
