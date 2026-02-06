<?php

namespace Database\Factories;

use App\Models\ServiceListing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ListingPhoto>
 */
class ListingPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => ServiceListing::factory(),
            'photo_url' => 'listing_photos/' . fake()->uuid() . '.jpg',
            'sort_order' => 0,
            'uploaded_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function forListing(ServiceListing $listing, int $sortOrder = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'listing_id' => $listing->id,
            'sort_order' => $sortOrder,
        ]);
    }

    public function sortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }
}