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
        // Create default admin user
        User::factory()->create([
            'first_name' => 'Dr Lab',
            'last_name' => 'Administrator',
            'email' => 'admin@drlab.com',
            'password' => Hash::make('password'),
            'user_type' => 'admin',
            'account_is_set' => true,
            'is_active' => true,
            'date_hired' => now(),
        ]);

        // Create sample lab technician
        User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'email' => 'john.smith@drlab.com',
            'user_type' => 'lab_technician',
            'account_is_set' => true,
            'is_active' => true,
            'date_hired' => now()->subMonths(3),
        ]);

        // Create a user that needs setup
        User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@drlab.com',
            'user_type' => 'quality_control',
            'account_is_set' => false,
            'is_active' => true,
            'date_hired' => now()->subDays(2),
            'last_login_at' => null,
        ]);

        // Create additional sample users
        User::factory(10)->create();
        
        // Create some inactive users
        User::factory(3)->create(['is_active' => false]);
        
        // Create users that need setup
        User::factory(2)->needsSetup()->create();
    }
}