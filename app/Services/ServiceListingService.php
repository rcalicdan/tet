<?php

namespace App\Services;

use App\Models\ServiceListing;
use App\Models\ListingPhoto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ServiceListingService
{
    public function getAllListings(array $filters = []): LengthAwarePaginator
    {
        $query = ServiceListing::query()
            ->with(['photos', 'contractor']);

        if (!empty($filters['contractor_id'])) {
            $query->where('contractor_id', $filters['contractor_id']);
        }

        $this->applyFilters($query, $filters);
        $this->applySearch($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    public function getContractorListings(string $contractorId, array $filters = []): LengthAwarePaginator
    {
        $query = ServiceListing::query()
            ->where('contractor_id', $contractorId)
            ->with(['photos', 'contractor']);

        $this->applyFilters($query, $filters);
        $this->applySearch($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['service_type'])) {
            $query->where('service_type', $filters['service_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['city'])) {
            $query->where('service_city', 'ILIKE', '%' . $filters['city'] . '%');
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (!empty($filters['latitude']) && !empty($filters['longitude'])) {
            $this->applyGeographicFilter($query, $filters);
        }
    }

    protected function applyGeographicFilter(Builder $query, array $filters): void
    {
        $latitude = $filters['latitude'];
        $longitude = $filters['longitude'];
        $radiusKm = $filters['radius_km'] ?? 50;

        $query->selectRaw("
        service_listings.*,
        (
            6371 * acos(
                cos(radians(?)) * 
                cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * 
                sin(radians(latitude))
            )
        ) AS distance
        ", [$latitude, $longitude, $latitude]);

        $query->whereRaw("
        (
            6371 * acos(
                cos(radians(?)) * 
                cos(radians(latitude)) * 
                cos(radians(longitude) - radians(?)) + 
                sin(radians(?)) * 
                sin(radians(latitude))
            )
        ) <= ?
        ", [$latitude, $longitude, $latitude, $radiusKm]);
    }

    protected function applySorting(Builder $query, array $filters): void
    {
        if (!empty($filters['search'])) {
            return;
        }

        $sortBy = $filters['sort_by'] ?? 'newest';

        match ($sortBy) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'price_asc' => $query->orderBy('price', 'asc')->orderBy('created_at', 'desc'),
            'price_desc' => $query->orderBy('price', 'desc')->orderBy('created_at', 'desc'),
            'service_type' => $query->orderBy('service_type', 'asc')->orderBy('created_at', 'desc'),
            'city' => $query->orderBy('service_city', 'asc')->orderBy('created_at', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };
    }


    protected function applySearch(Builder $query, array $filters): void
    {
        if (empty($filters['search'])) {
            return;
        }

        $searchTerm = $filters['search'];

        $query->where(function ($q) use ($searchTerm) {
            $q->whereRaw("searchable @@ websearch_to_tsquery('english', ?)", [$searchTerm])
                ->orWhere('service_type', 'ILIKE', '%' . $searchTerm . '%')
                ->orWhere('description', 'ILIKE', '%' . $searchTerm . '%')
                ->orWhere('service_city', 'ILIKE', '%' . $searchTerm . '%');
        })
            ->selectRaw("
            *,
            CASE 
                WHEN searchable @@ websearch_to_tsquery('english', ?) THEN 
                    ts_rank(searchable, websearch_to_tsquery('english', ?)) + 1
                ELSE 
                    GREATEST(
                        similarity(service_type, ?),
                        similarity(service_city, ?),
                        similarity(description, ?) / 2
                    )
            END as search_rank
        ", array_fill(0, 5, $searchTerm))
            ->orderByDesc('search_rank');
    }

    public function getContractorStats(string $contractorId): array
    {
        $listings = ServiceListing::where('contractor_id', $contractorId);

        return [
            'total' => $listings->count(),
            'active' => (clone $listings)->where('status', 'active')->count(),
            'inactive' => (clone $listings)->where('status', 'inactive')->count(),
            'pending' => (clone $listings)->where('status', 'pending')->count(),
            'total_photos' => ListingPhoto::whereIn(
                'listing_id',
                (clone $listings)->pluck('id')
            )->count(),
            'service_types' => (clone $listings)
                ->select('service_type', DB::raw('count(*) as count'))
                ->groupBy('service_type')
                ->pluck('count', 'service_type')
                ->toArray(),
        ];
    }

    public function getContractorServiceTypes(string $contractorId): array
    {
        return ServiceListing::where('contractor_id', $contractorId)
            ->distinct()
            ->pluck('service_type')
            ->sort()
            ->values()
            ->toArray();
    }

    public function getContractorCities(string $contractorId): array
    {
        return ServiceListing::where('contractor_id', $contractorId)
            ->distinct()
            ->pluck('service_city')
            ->sort()
            ->values()
            ->toArray();
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
        DB::transaction(function () use ($listing) {
            foreach ($listing->photos as $photo) {
                Storage::disk('public')->delete($photo->photo_url);
            }

            $listing->delete();
        });
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
}
