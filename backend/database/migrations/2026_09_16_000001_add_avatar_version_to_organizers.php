<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Disable the implicit transaction wrapper for this migration.
    // On managed PostgreSQL (Render, RDS, etc.), a prior statement failure
    // in the same transaction aborts the entire transaction, causing
    // subsequent CREATE TABLE/ALTER TABLE to fail with "current transaction is aborted".
    // By opting out, each statement runs in its own autocommit transaction.
    public $withinTransaction = false;
    public function up(): void
    {
        if (!Schema::hasTable('organizers')) return;
        $hasOrganizersAvatarversion = Schema::hasColumn('organizers', 'avatarVersion');
        Schema::table('organizers', function (Blueprint $table) use ($hasOrganizersAvatarversion) {
            if (!$hasOrganizersAvatarversion) {
                $table->unsignedInteger('avatarVersion')->default(0);
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
