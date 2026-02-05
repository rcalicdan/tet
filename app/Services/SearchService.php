<?php

namespace App\Services;

use App\Models\ServiceListing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    public function searchListings(array $filters): LengthAwarePaginator
    {
        $query = ServiceListing::query()
            ->with(['photos', 'contractor'])
            ->where('status', 'active');

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['service_type'])) {
            $query->where('service_type', 'like', '%' . $filters['service_type'] . '%');
        }

        if (!empty($filters['city'])) {
            $query->where('service_city', 'like', '%' . $filters['city'] . '%');
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
            *,
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

        $query->having('distance', '<=', $radiusKm);
    }

    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'newest';

        match($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'distance' => $query->orderBy('distance', 'asc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };
    }

    public function getPopularServiceTypes(int $limit = 10): array
    {
        return ServiceListing::query()
            ->where('status', 'active')
            ->selectRaw('service_type, COUNT(*) as count')
            ->groupBy('service_type')
            ->orderBy('count', 'desc')
            ->limit($limit)
            ->pluck('count', 'service_type')
            ->toArray();
    }

    public function getNearbyListings(float $latitude, float $longitude, int $radiusKm = 10, int $limit = 10)
    {
        return ServiceListing::query()
            ->with(['photos', 'contractor'])
            ->where('status', 'active')
            ->selectRaw("
                *,
                (
                    6371 * acos(
                        cos(radians(?)) * 
                        cos(radians(latitude)) * 
                        cos(radians(longitude) - radians(?)) + 
                        sin(radians(?)) * 
                        sin(radians(latitude))
                    )
                ) AS distance
            ", [$latitude, $longitude, $latitude])
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance', 'asc')
            ->limit($limit)
            ->get();
    }

    public function getSimilarListings(ServiceListing $listing, int $limit = 5)
    {
        $query = ServiceListing::query()
            ->with(['photos', 'contractor'])
            ->where('status', 'active')
            ->where('id', '!=', $listing->id)
            ->where('service_type', $listing->service_type);

        if ($listing->latitude && $listing->longitude) {
            $query->selectRaw("
                *,
                (
                    6371 * acos(
                        cos(radians(?)) * 
                        cos(radians(latitude)) * 
                        cos(radians(longitude) - radians(?)) + 
                        sin(radians(?)) * 
                        sin(radians(latitude))
                    )
                ) AS distance
            ", [$listing->latitude, $listing->longitude, $listing->latitude])
            ->orderBy('distance', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->limit($limit)->get();
    }
}