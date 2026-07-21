<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->string('category')->nullable()->after('description');
            $table->string('riskLevel')->nullable()->after('category');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'riskLevel']);
            $table->string('description')->nullable()->change();
        });
    }
};