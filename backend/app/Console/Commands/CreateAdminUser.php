<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create 
                            {name : Admin name} 
                            {email : Admin email} 
                            {password : Admin password}';

    protected $description = 'Create an admin user and output a bearer token';

    public function handle(): void
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $password = $this->argument('password');

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrator', 'isSystemRole' => true]
        );

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'passwordHash' => Hash::make($password),
                'role' => 'admin',
                'role_id' => $adminRole->id,
                'emailVerified' => true,
                'status' => 'active',
            ]
        );

        $user->roles()->syncWithoutDetaching([$adminRole->id]);

        $token = $user->createToken('admin-cli')->plainTextToken;

        $this->info('Admin user created/updated successfully.');
        $this->line('Email: ' . $email);
        $this->line('Bearer token: ' . $token);
    }
}
