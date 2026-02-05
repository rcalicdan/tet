<?php

namespace App\Services;

use App\Models\ServiceListing;
use App\Models\ListingPhoto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceListingService
{
    public function getContractorListings(string $contractorId)
    {
        return ServiceListing::where('contractor_id', $contractorId)
            ->with(['photos', 'contractor'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createListing(array $data, string $contractorId): ServiceListing
    {
        return DB::transaction(function () use ($data, $contractorId) {
            $listing = ServiceListing::create([
                'contractor_id' => $contractorId,
                'service_type' => $data['service_type'],
                'description' => $data['description'],
                'price' => $data['price'] ?? null,
                'service_city' => $data['service_city'],
                'service_radius_km' => $data['service_radius_km'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'contact_phone' => $data['contact_phone'],
                'status' => 'active',
            ]);

            if (isset($data['photos'])) {
                $this->uploadPhotos($listing, $data['photos']);
            }

            return $listing->load(['photos', 'contractor']);
        });
    }

    public function updateListing(ServiceListing $listing, array $data): ServiceListing
    {
        $listing->update($data);
        return $listing->load(['photos', 'contractor']);
    }

    public function deleteListing(ServiceListing $listing): void
    {
        foreach ($listing->photos as $photo) {
            Storage::disk('public')->delete($photo->photo_url);
        }

        $listing->delete();
    }

    public function addPhotos(ServiceListing $listing, array $photos): void
    {
        $currentPhotoCount = $listing->photos()->count();
        
        if ($currentPhotoCount + count($photos) > 10) {
            throw new \Exception('Maximum 10 photos allowed per listing');
        }

        $this->uploadPhotos($listing, $photos);
    }

    public function deletePhoto(ListingPhoto $photo): void
    {
        Storage::disk('public')->delete($photo->photo_url);
        $photo->delete();
    }

    public function reorderPhotos(ServiceListing $listing, array $photoOrder): void
    {
        DB::transaction(function () use ($listing, $photoOrder) {
            foreach ($photoOrder as $photoData) {
                ListingPhoto::where('id', $photoData['id'])
                    ->where('listing_id', $listing->id)
                    ->update(['sort_order' => $photoData['sort_order']]);
            }
        });
    }

    public function toggleStatus(ServiceListing $listing): string
    {
        $newStatus = $listing->status === 'active' ? 'inactive' : 'active';
        $listing->update(['status' => $newStatus]);
        
        return $newStatus;
    }

    protected function uploadPhotos(ServiceListing $listing, array $photos): void
    {
        $sortOrder = $listing->photos()->max('sort_order') ?? -1;

        foreach ($photos as $photo) {
            $sortOrder++;
            
            $path = $photo->store('listings/' . $listing->id, 'public');

            ListingPhoto::create([
                'listing_id' => $listing->id,
                'photo_url' => $path,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    public function authorizeContractor(ServiceListing $listing, string $contractorId): bool
    {
        return $listing->contractor_id === $contractorId;
    }
}