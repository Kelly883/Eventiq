<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('role_id')->nullable()->after('emailVerified');
            $table->json('permissions')->nullable()->after('role_id');
            
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropIndex(['role_id']);
            $table->dropColumn(['role_id', 'permissions']);
        });
    }
};