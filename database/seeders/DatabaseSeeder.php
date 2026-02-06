<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ServiceListing;
use App\Models\ListingPhoto;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $testClient = User::factory()->client()->create([
            'first_name' => 'Test',
            'last_name' => 'Client',
            'email' => 'client@example.com',
        ]);

        $testContractor = User::factory()->contractor()->create([
            'first_name' => 'Test',
            'last_name' => 'Contractor',
            'email' => 'contractor@example.com',
        ]);

        User::factory()->client()->count(10)->create();

        $contractors = User::factory()->contractor()->count(15)->create();

        $contractors->each(function ($contractor) {
            $listingsCount = rand(1, 3);
            
            for ($i = 0; $i < $listingsCount; $i++) {
                $listing = ServiceListing::factory()
                    ->forContractor($contractor)
                    ->active()
                    ->create();

                $photosCount = rand(2, 5);
                for ($j = 0; $j < $photosCount; $j++) {
                    ListingPhoto::factory()
                        ->forListing($listing, $j)
                        ->create();
                }
            }
        });

        for ($i = 0; $i < 3; $i++) {
            $listing = ServiceListing::factory()
                ->forContractor($testContractor)
                ->active()
                ->create();

            for ($j = 0; $j < 4; $j++) {
                ListingPhoto::factory()
                    ->forListing($listing, $j)
                    ->create();
            }
        }
    }
}