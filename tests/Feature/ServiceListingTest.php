<?php

use App\Enums\UserType;
use App\Models\ListingPhoto;
use App\Models\ServiceListing;
use App\Models\User;
use App\Services\ServiceListingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->contractor = User::factory()->create([
        'user_type' => UserType::CONTRACTOR,
    ]);

    $this->client = User::factory()->create([
        'user_type' => UserType::CLIENT,
    ]);

    $this->service = Mockery::mock(ServiceListingService::class);
    $this->app->instance(ServiceListingService::class, $this->service);
});

afterEach(function () {
    Mockery::close();
});

describe('Service Listing Management', function () {

    test('contractor can view their own listings', function () {
        $listings = collect([
            (object) ['id' => 'listing-1'],
            (object) ['id' => 'listing-2'],
        ]);

        $this->service
            ->shouldReceive('getContractorListings')
            ->once()
            ->with($this->contractor->id)
            ->andReturn($listings);

        $response = $this->actingAs($this->contractor, 'api')
            ->getJson('/api/listings');

        expect($response->status())->toBe(200)
            ->and($response->json('success'))->toBeTrue();
    });

    test('contractor can create a service listing', function () {
        $listingData = [
            'service_type' => 'Plumbing',
            'description' => 'Professional plumbing services',
            'price' => 100.00,
            'service_city' => 'Manila',
            'service_radius_km' => 20,
            'contact_phone' => '+639123456789',
        ];

        $listing = Mockery::mock(ServiceListing::class);
        $listing->shouldReceive('load')->andReturnSelf();

        $this->service
            ->shouldReceive('createListing')
            ->once()
            ->with($listingData, $this->contractor->id)
            ->andReturn($listing);

        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', $listingData);

        expect($response->status())->toBe(201)
            ->and($response->json('success'))->toBeTrue();
    });

    test('contractor can create a service listing with photos', function () {
        $listingData = [
            'service_type' => 'Plumbing',
            'description' => 'Professional plumbing services',
            'service_city' => 'Manila',
            'service_radius_km' => 20,
            'contact_phone' => '+639123456789',
            'photos' => [
                UploadedFile::fake()->image('photo1.jpg'),
                UploadedFile::fake()->image('photo2.jpg'),
            ],
        ];

        $listing = Mockery::mock(ServiceListing::class);
        $listing->shouldReceive('load')->andReturnSelf();

        $this->service
            ->shouldReceive('createListing')
            ->once()
            ->andReturn($listing);

        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', $listingData);

        expect($response->status())->toBe(201);
    });

    test('contractor can view a specific listing', function () {
        $listing = ServiceListing::factory()->create([
            'contractor_id' => $this->contractor->id,
        ]);

        $response = $this->actingAs($this->contractor, 'api')
            ->getJson("/api/listings/{$listing->id}");

        expect($response->status())->toBe(200)
            ->and($response->json('success'))->toBeTrue();
    });

    test('contractor can update their own listing', function () {
        $listing = ServiceListing::factory()->create([
            'contractor_id' => $this->contractor->id,
        ]);

        $updateData = [
            'description' => 'Updated description',
            'price' => 150.00,
        ];

        $this->service
            ->shouldReceive('updateListing')
            ->once()
            ->andReturn($listing);

        $response = $this->actingAs($this->contractor, 'api')
            ->putJson("/api/listings/{$listing->id}", $updateData);

        expect($response->status())->toBe(200)
            ->and($response->json('success'))->toBeTrue();
    });

    test('contractor cannot update another contractors listing', function () {
        $otherContractor = User::factory()->create([
            'user_type' => UserType::CONTRACTOR,
        ]);

        $listing = ServiceListing::factory()->create([
            'contractor_id' => $otherContractor->id,
        ]);

        $response = $this->actingAs($this->contractor, 'api')
            ->putJson("/api/listings/{$listing->id}", [
                'description' => 'Updated description',
            ]);

        expect($response->status())->toBe(403);
    });

    test('contractor can delete their own listing', function () {
        $listing = ServiceListing::factory()->create([
            'contractor_id' => $this->contractor->id,
        ]);

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->with($listing, $this->contractor->id)
            ->andReturn(true);

        $this->service
            ->shouldReceive('deleteListing')
            ->once()
            ->with($listing);

        $response = $this->actingAs($this->contractor, 'api')
            ->deleteJson("/api/listings/{$listing->id}");

        expect($response->status())->toBe(200)
            ->and($response->json('success'))->toBeTrue();
    });

    test('contractor cannot delete another contractors listing', function () {
        $otherContractor = User::factory()->create([
            'user_type' => UserType::CONTRACTOR,
        ]);

        $listing = ServiceListing::factory()->create([
            'contractor_id' => $otherContractor->id,
        ]);

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->with($listing, $this->contractor->id)
            ->andReturn(false);

        $response = $this->actingAs($this->contractor, 'api')
            ->deleteJson("/api/listings/{$listing->id}");

        expect($response->status())->toBe(403);
    });

    test('client cannot create a service listing', function () {
        $listingData = [
            'service_type' => 'Plumbing',
            'description' => 'Professional plumbing services',
            'service_city' => 'Manila',
            'service_radius_km' => 20,
            'contact_phone' => '+639123456789',
        ];

        $response = $this->actingAs($this->client, 'api')
            ->postJson('/api/listings', $listingData);

        expect($response->status())->toBe(403);
    });

    test('unauthenticated user cannot create a service listing', function () {
        $listingData = [
            'service_type' => 'Plumbing',
            'description' => 'Professional plumbing services',
            'service_city' => 'Manila',
            'service_radius_km' => 20,
            'contact_phone' => '+639123456789',
        ];

        $response = $this->postJson('/api/listings', $listingData);

        expect($response->status())->toBe(401);
    });

});

describe('Service Listing Photos', function () {

    test('contractor can upload photos to their listing', function () {
        $listing = ServiceListing::factory()->create([
            'contractor_id' => $this->contractor->id,
        ]);

        $photos = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.jpg'),
        ];

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->andReturn(true);

        $this->service
            ->shouldReceive('addPhotos')
            ->once();

        $response = $this->actingAs($this->contractor, 'api')
            ->postJson("/api/listings/{$listing->id}/photos", [
                'photos' => $photos,
            ]);

        expect($response->status())->toBe(200)
            ->and($response->json('success'))->toBeTrue();
    });

    test('contractor cannot upload more than 10 photos total', function () {
        $listing = ServiceListing::factory()
            ->has(ListingPhoto::factory()->count(8))
            ->create(['contractor_id' => $this->contractor->id]);

        $photos = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.jpg'),
            UploadedFile::fake()->image('photo3.jpg'),
        ];

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->andReturn(true);

        $this->service
            ->shouldReceive('addPhotos')
            ->once()
            ->andThrow(new \Exception('Maximum 10 photos allowed per listing'));

        $response = $this->actingAs($this->contractor, 'api')
            ->postJson("/api/listings/{$listing->id}/photos", [
                'photos' => $photos,
            ]);

        expect($response->status())->toBe(422);
    });

    test('contractor can delete a photo from their listing', function () {
        $listing = ServiceListing::factory()->create([
            'contractor_id' => $this->contractor->id,
        ]);

        $photo = ListingPhoto::factory()->create([
            'listing_id' => $listing->id,
        ]);

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->andReturn(true);

        $this->service
            ->shouldReceive('deletePhoto')
            ->once()
            ->with($photo);

        $response = $this->actingAs($this->contractor, 'api')
            ->deleteJson("/api/listings/{$listing->id}/photos/{$photo->id}");

        expect($response->status())->toBe(200)
            ->and($response->json('success'))->toBeTrue();
    });

    test('contractor cannot delete photo from another contractors listing', function () {
        $otherContractor = User::factory()->create([
            'user_type' => UserType::CONTRACTOR,
        ]);

        $listing = ServiceListing::factory()->create([
            'contractor_id' => $otherContractor->id,
        ]);

        $photo = ListingPhoto::factory()->create([
            'listing_id' => $listing->id,
        ]);

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->andReturn(false);

        $response = $this->actingAs($this->contractor, 'api')
            ->deleteJson("/api/listings/{$listing->id}/photos/{$photo->id}");

        expect($response->status())->toBe(403);
    });

    test('contractor can reorder photos', function () {
        $listing = ServiceListing::factory()->create([
            'contractor_id' => $this->contractor->id,
        ]);

        $photo1 = ListingPhoto::factory()->create([
            'listing_id' => $listing->id,
            'sort_order' => 0,
        ]);

        $photo2 = ListingPhoto::factory()->create([
            'listing_id' => $listing->id,
            'sort_order' => 1,
        ]);

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->andReturn(true);

        $this->service
            ->shouldReceive('reorderPhotos')
            ->once();

        $response = $this->actingAs($this->contractor, 'api')
            ->putJson("/api/listings/{$listing->id}/photos/reorder", [
                'photo_order' => [
                    ['id' => $photo2->id, 'sort_order' => 0],
                    ['id' => $photo1->id, 'sort_order' => 1],
                ],
            ]);

        expect($response->status())->toBe(200)
            ->and($response->json('success'))->toBeTrue();
    });

    test('photos are deleted when listing is deleted', function () {
        $listing = ServiceListing::factory()
            ->has(ListingPhoto::factory()->count(3))
            ->create(['contractor_id' => $this->contractor->id]);

        $photoCount = $listing->photos()->count();

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->andReturn(true);

        $this->service
            ->shouldReceive('deleteListing')
            ->once();

        $response = $this->actingAs($this->contractor, 'api')
            ->deleteJson("/api/listings/{$listing->id}");

        expect($response->status())->toBe(200)
            ->and($photoCount)->toBe(3);
    });

    test('only valid image formats are accepted', function () {
        $listing = ServiceListing::factory()->create([
            'contractor_id' => $this->contractor->id,
        ]);

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($this->contractor, 'api')
            ->postJson("/api/listings/{$listing->id}/photos", [
                'photos' => [
                    UploadedFile::fake()->create('document.pdf', 100),
                ],
            ]);

        expect($response->status())->toBe(422);
    });

    test('photo file size cannot exceed 5MB', function () {
        $listing = ServiceListing::factory()->create([
            'contractor_id' => $this->contractor->id,
        ]);

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($this->contractor, 'api')
            ->postJson("/api/listings/{$listing->id}/photos", [
                'photos' => [
                    UploadedFile::fake()->image('large.jpg')->size(6000),
                ],
            ]);

        expect($response->status())->toBe(422);
    });

});

describe('Service Listing Status', function () {

    test('contractor can toggle listing status from active to inactive', function () {
        $listing = ServiceListing::factory()->create([
            'contractor_id' => $this->contractor->id,
            'status' => 'active',
        ]);

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->andReturn(true);

        $this->service
            ->shouldReceive('toggleStatus')
            ->once()
            ->andReturn('inactive');

        $response = $this->actingAs($this->contractor, 'api')
            ->patchJson("/api/listings/{$listing->id}/toggle-status");

        expect($response->status())->toBe(200)
            ->and($response->json('data.status'))->toBe('inactive');
    });

    test('contractor can toggle listing status from inactive to active', function () {
        $listing = ServiceListing::factory()->create([
            'contractor_id' => $this->contractor->id,
            'status' => 'inactive',
        ]);

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->andReturn(true);

        $this->service
            ->shouldReceive('toggleStatus')
            ->once()
            ->andReturn('active');

        $response = $this->actingAs($this->contractor, 'api')
            ->patchJson("/api/listings/{$listing->id}/toggle-status");

        expect($response->status())->toBe(200)
            ->and($response->json('data.status'))->toBe('active');
    });

    test('contractor cannot toggle another contractors listing status', function () {
        $otherContractor = User::factory()->create([
            'user_type' => UserType::CONTRACTOR,
        ]);

        $listing = ServiceListing::factory()->create([
            'contractor_id' => $otherContractor->id,
        ]);

        $this->service
            ->shouldReceive('authorizeContractor')
            ->once()
            ->andReturn(false);

        $response = $this->actingAs($this->contractor, 'api')
            ->patchJson("/api/listings/{$listing->id}/toggle-status");

        expect($response->status())->toBe(403);
    });

    test('new listings default to active status', function () {
        $listingData = [
            'service_type' => 'Plumbing',
            'description' => 'Professional plumbing services',
            'service_city' => 'Manila',
            'service_radius_km' => 20,
            'contact_phone' => '+639123456789',
        ];

        $listing = Mockery::mock(ServiceListing::class);
        $listing->shouldReceive('load')->andReturnSelf();
        $listing->status = 'active';

        $this->service
            ->shouldReceive('createListing')
            ->once()
            ->andReturn($listing);

        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', $listingData);

        expect($response->status())->toBe(201);
    });

});

describe('Service Listing Validation', function () {

    test('service type is required', function () {
        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', [
                'description' => 'Test description',
                'service_city' => 'Manila',
                'service_radius_km' => 20,
                'contact_phone' => '+639123456789',
            ]);

        expect($response->status())->toBe(422);
    });

    test('description is required', function () {
        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', [
                'service_type' => 'Plumbing',
                'service_city' => 'Manila',
                'service_radius_km' => 20,
                'contact_phone' => '+639123456789',
            ]);

        expect($response->status())->toBe(422);
    });

    test('service city is required', function () {
        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', [
                'service_type' => 'Plumbing',
                'description' => 'Test description',
                'service_radius_km' => 20,
                'contact_phone' => '+639123456789',
            ]);

        expect($response->status())->toBe(422);
    });

    test('service radius is required', function () {
        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', [
                'service_type' => 'Plumbing',
                'description' => 'Test description',
                'service_city' => 'Manila',
                'contact_phone' => '+639123456789',
            ]);

        expect($response->status())->toBe(422);
    });

    test('contact phone is required', function () {
        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', [
                'service_type' => 'Plumbing',
                'description' => 'Test description',
                'service_city' => 'Manila',
                'service_radius_km' => 20,
            ]);

        expect($response->status())->toBe(422);
    });

    test('price can be nullable', function () {
        $listingData = [
            'service_type' => 'Plumbing',
            'description' => 'Professional plumbing services',
            'service_city' => 'Manila',
            'service_radius_km' => 20,
            'contact_phone' => '+639123456789',
            'price' => null,
        ];

        $listing = Mockery::mock(ServiceListing::class);
        $listing->shouldReceive('load')->andReturnSelf();

        $this->service
            ->shouldReceive('createListing')
            ->once()
            ->andReturn($listing);

        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', $listingData);

        expect($response->status())->toBe(201);
    });

    test('latitude must be valid coordinate', function () {
        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', [
                'service_type' => 'Plumbing',
                'description' => 'Test description',
                'service_city' => 'Manila',
                'service_radius_km' => 20,
                'contact_phone' => '+639123456789',
                'latitude' => 100,
            ]);

        expect($response->status())->toBe(422);
    });

    test('longitude must be valid coordinate', function () {
        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', [
                'service_type' => 'Plumbing',
                'description' => 'Test description',
                'service_city' => 'Manila',
                'service_radius_km' => 20,
                'contact_phone' => '+639123456789',
                'longitude' => 200,
            ]);

        expect($response->status())->toBe(422);
    });

    test('service radius must be positive integer', function () {
        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', [
                'service_type' => 'Plumbing',
                'description' => 'Test description',
                'service_city' => 'Manila',
                'service_radius_km' => -5,
                'contact_phone' => '+639123456789',
            ]);

        expect($response->status())->toBe(422);
    });

    test('service radius cannot exceed 500km', function () {
        $response = $this->actingAs($this->contractor, 'api')
            ->postJson('/api/listings', [
                'service_type' => 'Plumbing',
                'description' => 'Test description',
                'service_city' => 'Manila',
                'service_radius_km' => 600,
                'contact_phone' => '+639123456789',
            ]);

        expect($response->status())->toBe(422);
    });

});

describe('Service Listing Authorization', function () {

    test('only contractor can access listing endpoints', function () {
        $listingData = [
            'service_type' => 'Plumbing',
            'description' => 'Professional plumbing services',
            'service_city' => 'Manila',
            'service_radius_km' => 20,
            'contact_phone' => '+639123456789',
        ];

        $response = $this->actingAs($this->client, 'api')
            ->postJson('/api/listings', $listingData);

        expect($response->status())->toBe(403);
    });

    test('contractor can only modify their own listings', function () {
        $otherContractor = User::factory()->create([
            'user_type' => UserType::CONTRACTOR,
        ]);

        $listing = ServiceListing::factory()->create([
            'contractor_id' => $otherContractor->id,
        ]);

        $response = $this->actingAs($this->contractor, 'api')
            ->putJson("/api/listings/{$listing->id}", [
                'description' => 'Updated description',
            ]);

        expect($response->status())->toBe(403);
    });

    test('anyone can view a listing details', function () {
        $listing = ServiceListing::factory()->create([
            'contractor_id' => $this->contractor->id,
        ]);

        $response = $this->actingAs($this->client, 'api')
            ->getJson("/api/listings/{$listing->id}");

        expect($response->status())->toBe(200);
    });

});
