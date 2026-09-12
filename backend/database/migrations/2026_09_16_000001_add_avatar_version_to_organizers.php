<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('organizers')) return;
        Schema::table('organizers', function (Blueprint $table) {
            if (!Schema::hasColumn('organizers', 'avatarVersion')) {
                $table->unsignedInteger('avatarVersion')->default(0)->after('avatarUrl');
                $table->index('avatarVersion');
            }
        });
        // Backfill existing rows
        try {
            \Illuminate\Support\Facades\DB::table('organizers')->whereNull('avatarVersion')->update(['avatarVersion' => 0]);
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        if (Schema::hasTable('organizers') && Schema::hasColumn('organizers', 'avatarVersion')) {
            Schema::table('organizers', function (Blueprint $table) {
                $table->dropColumn('avatarVersion');
            });
        }
    }
};
