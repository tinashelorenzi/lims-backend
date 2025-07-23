<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone_number' => fake()->phoneNumber(),
            'password' => static::$password ??= Hash::make('password'),
            'date_hired' => fake()->dateTimeBetween('-2 years', 'now'),
            'last_login_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
            'user_type' => fake()->randomElement(array_keys(User::USER_TYPES)),
            'account_is_set' => fake()->boolean(80), // 80% chance account is set
            'is_active' => fake()->boolean(90), // 90% chance user is active
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'admin',
            'account_is_set' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Create a lab technician user.
     */
    public function labTechnician(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => 'lab_technician',
        ]);
    }

    /**
     * Create a user that needs account setup.
     */
    public function needsSetup(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_is_set' => false,
            'last_login_at' => null,
        ]);
    }
}