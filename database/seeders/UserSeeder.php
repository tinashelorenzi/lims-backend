<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@drlab.com',
            'password' => Hash::make('password'),
            'phone_number' => '(555) 123-4567',
            'date_hired' => now()->subYear(),
            'user_type' => 'admin',
            'account_is_set' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Create sample users
        User::factory()->admin()->create([
            'first_name' => 'John',
            'last_name' => 'Administrator',
            'email' => 'john.admin@drlab.com',
        ]);

        User::factory()->labTechnician()->create([
            'first_name' => 'Jane',
            'last_name' => 'Technician',
            'email' => 'jane.tech@drlab.com',
        ]);

        User::factory()->needsSetup()->create([
            'first_name' => 'New',
            'last_name' => 'Employee',
            'email' => 'new.employee@drlab.com',
            'user_type' => 'lab_technician',
        ]);

        // Create additional random users
        User::factory(10)->create();
    }
}