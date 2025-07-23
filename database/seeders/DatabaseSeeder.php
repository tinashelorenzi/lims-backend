<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create or update default admin user
        User::updateOrCreate(
            ['email' => 'admin@drlab.com'],
            [
                'first_name' => 'Dr Lab',
                'last_name' => 'Administrator',
                'password' => Hash::make('password'),
                'user_type' => 'admin',
                'account_is_set' => true,
                'is_active' => true,
                'date_hired' => now(),
            ]
        );

        // Create or update sample lab technician
        User::updateOrCreate(
            ['email' => 'john.smith@drlab.com'],
            [
                'first_name' => 'John',
                'last_name' => 'Smith',
                'user_type' => 'lab_technician',
                'account_is_set' => true,
                'is_active' => true,
                'date_hired' => now()->subMonths(3),
            ]
        );

        // Create or update a user that needs setup
        User::updateOrCreate(
            ['email' => 'jane.doe@drlab.com'],
            [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'user_type' => 'quality_control',
                'account_is_set' => false,
                'is_active' => true,
                'date_hired' => now()->subDays(2),
                'last_login_at' => null,
            ]
        );

        // Create additional sample users (only if they don't exist)
        if (User::count() < 20) {
            User::factory(10)->create();
            
            // Create some inactive users
            User::factory(3)->create(['is_active' => false]);
            
            // Create users that need setup
            User::factory(2)->needsSetup()->create();
        }
    }
}