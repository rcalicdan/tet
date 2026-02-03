<?php

namespace Database\Factories;

use App\Enums\UserType;
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
    protected static ?string $password = null;

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
            'phone_number' => '+48' . fake()->numerify('#########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'user_type' => fake()->randomElement(UserType::cases()),
            'is_active' => true,
            'bio' => fake()->optional()->paragraph(),
            'city' => fake()->optional()->city(),
            'address' => fake()->optional()->address(),
            'remember_token' => Str::random(10),
        ];
    }

    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => UserType::CLIENT,
            'bio' => null, 
        ]);
    }

    public function contractor(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => UserType::CONTRACTOR,
            'bio' => fake()->paragraph(),
            'phone_number' => '+48' . fake()->numerify('#########'),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withGoogle(): static
    {
        return $this->state(fn (array $attributes) => [
            'google_id' => 'google_' . Str::random(20),
            'password' => null,
        ]);
    }

    public function withApple(): static
    {
        return $this->state(fn (array $attributes) => [
            'apple_id' => 'apple_' . Str::random(20),
            'password' => null,
        ]);
    }

    public function withPhoto(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_photo' => 'profile_photos/' . fake()->uuid() . '.jpg',
        ]);
    }

    public function complete(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => '+48' . fake()->numerify('#########'),
            'bio' => fake()->paragraph(),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'profile_photo' => 'profile_photos/' . fake()->uuid() . '.jpg',
        ]);
    }
}