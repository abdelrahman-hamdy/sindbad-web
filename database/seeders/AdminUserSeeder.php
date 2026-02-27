<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['phone' => '96891234567'],
            [
                'name' => 'Admin',
                'email' => 'admin@sindbad.om',
                'phone' => '96891234567',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin->value,
                'is_active' => true,
            ]
        );

        $admin->assignRole(UserRole::Admin->value);

        $this->command->info('Admin user created: phone=96891234567, password=password');
    }
}
