<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        if (app()->environment('testing')) {
            return;
        }

        $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

        if (!$adminRoleId) {
            $adminRoleId = DB::table('roles')->insertGetId([
                'name' => 'admin',
                'description' => 'Administrator',
                'isSystemRole' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')->updateOrInsert(
            ['email' => 'keltech2025@gmail.com'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Kelechi',
                'passwordHash' => Hash::make('Kelly94#_'),
                'role' => 'admin',
                'role_id' => $adminRoleId,
                'emailVerified' => true,
                'status' => 'active',
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'keltech2025@gmail.com')->delete();
    }
};
