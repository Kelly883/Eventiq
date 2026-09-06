<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator', 'isSystemRole' => true]
        );

        $attendeeRole = Role::firstOrCreate(
            ['name' => 'attendee'],
            ['description' => 'Attendee']
        );

        User::updateOrCreate(
            ['email' => 'admin@eventiq.test'],
            [
                'name' => 'Test Admin',
                'passwordHash' => Hash::make('password'),
                'role' => 'admin',
                'emailVerified' => true,
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'attendee@eventiq.test'],
            [
                'name' => 'Test Attendee',
                'passwordHash' => Hash::make('password'),
                'role' => 'attendee',
                'emailVerified' => true,
                'status' => 'active',
            ]
        );
    }
}
