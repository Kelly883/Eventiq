<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('business_name');
            $table->string('avatar_url')->nullable()->after('display_name');
            $table->string('email')->nullable()->after('avatar_url');
            $table->string('phone')->nullable()->after('email');
            $table->boolean('is_public')->default(true)->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('organizers', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'avatar_url',
                'email',
                'phone',
                'is_public',
            ]);
        });
    }
};
