<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator', 'isSystemRole' => true]
        );

        User::updateOrCreate(
            ['email' => 'keltech2025@gmail.com'],
            [
                'name' => 'Kelechi',
                'passwordHash' => Hash::make('Kelly94#_'),
                'role' => 'admin',
                'role_id' => $adminRole->id,
                'emailVerified' => true,
                'status' => 'active',
            ]
        );
    }

    public function down(): void
    {
        User::where('email', 'keltech2025@gmail.com')->delete();
    }
};
