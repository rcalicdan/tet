<?php

namespace Database\Factories;

use App\Models\ServiceListing;
use App\Models\User;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceListing>
 */
class ServiceListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $serviceTypes = [
            'Hydraulik',
            'Elektryk',
            'Malarz',
            'Ślusarz',
            'Glazurnik',
            'Stolarz',
            'Ogrodnik',
            'Sprzątanie',
        ];

        $cities = [
            ['name' => 'Warszawa', 'lat' => 52.2297, 'lng' => 21.0122],
            ['name' => 'Kraków', 'lat' => 50.0647, 'lng' => 19.9450],
            ['name' => 'Wrocław', 'lat' => 51.1079, 'lng' => 17.0385],
            ['name' => 'Poznań', 'lat' => 52.4064, 'lng' => 16.9252],
            ['name' => 'Gdańsk', 'lat' => 54.3520, 'lng' => 18.6466],
        ];

        $city = fake()->randomElement($cities);

        return [
            'contractor_id' => User::factory()->contractor(),
            'service_type' => fake()->randomElement($serviceTypes),
            'description' => fake()->paragraphs(3, true),
            'price' => fake()->randomFloat(2, 50, 500),
            'service_city' => $city['name'],
            'service_radius_km' => fake()->numberBetween(5, 50),
            'latitude' => $city['lat'] + fake()->randomFloat(4, -0.1, 0.1),
            'longitude' => $city['lng'] + fake()->randomFloat(4, -0.1, 0.1),
            'contact_phone' => '+48' . fake()->numerify('#########'),
            'status' => fake()->randomElement(['active', 'inactive', 'pending']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function forContractor(User $contractor): static
    {
        return $this->state(fn (array $attributes) => [
            'contractor_id' => $contractor->id,
        ]);
    }
}