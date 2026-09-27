<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
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
