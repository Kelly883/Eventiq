<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Organizer;
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

        $organizerRole = Role::firstOrCreate(
            ['name' => 'organizer'],
            ['description' => 'Organizer']
        );

        User::unguarded(fn() => User::updateOrCreate(
            ['email' => 'admin@eventiq.test'],
            [
                'name' => 'Test Admin',
                'passwordHash' => Hash::make('password'),
                'role' => 'admin',
                'emailVerified' => true,
                'status' => 'active',
            ]
        ));

        User::unguarded(fn() => User::updateOrCreate(
            ['email' => 'attendee@eventiq.test'],
            [
                'name' => 'Test Attendee',
                'passwordHash' => Hash::make('password'),
                'role' => 'attendee',
                'emailVerified' => true,
                'status' => 'active',
            ]
        ));

        $organizerUser = User::unguarded(fn() => User::updateOrCreate(
            ['email' => 'organizer@eventiq.test'],
            [
                'name' => 'Test Organizer',
                'passwordHash' => Hash::make('password'),
                'role' => 'organizer',
                'emailVerified' => true,
                'status' => 'active',
            ]
        ));

        Organizer::updateOrCreate(
            ['user_id' => $organizerUser->id],
            [
                'displayName' => 'Test Organizer',
            ]
        );
    }
}
