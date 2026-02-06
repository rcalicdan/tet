<?php

namespace App\Services;

use App\Models\ServiceListing;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SearchService
{
    public function searchListings(array $filters): LengthAwarePaginator
    {
        $query = ServiceListing::query()
            ->with(['photos', 'contractor'])
            ->where('status', 'active');

        $this->applyFilters($query, $filters);
        $this->applyFullTextSearch($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    protected function applyFullTextSearch(Builder $query, array $filters): void
    {
        if (empty($filters['query'])) {
            return;
        }

        $searchTerm = $filters['query'];
        $searchMode = $filters['search_mode'] ?? 'websearch'; 

        $tsQuery = match($searchMode) {
            'phrase' => "phraseto_tsquery('english', ?)",
            'plainto' => "plainto_tsquery('english', ?)",
            default => "websearch_to_tsquery('english', ?)", 
        };

        $query->selectRaw("
            service_listings.*,
            ts_rank(searchable, {$tsQuery}) as search_rank
        ", [$searchTerm])
        ->whereRaw("searchable @@ {$tsQuery}", [$searchTerm])
        ->orderByDesc('search_rank');
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['service_type'])) {
            $query->where('service_type', $filters['service_type']);
        }

        if (!empty($filters['city'])) {
            $query->where(function($q) use ($filters) {
                $q->where('service_city', 'ILIKE', '%' . $filters['city'] . '%')
                  ->orWhereRaw('service_city % ?', [$filters['city']]); 
            });
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

        $query->havingRaw('distance <= ?', [$radiusKm]);
    }

    protected function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'relevance';

        match($sortBy) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'distance' => $query->orderBy('distance', 'asc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            'relevance' => null,
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
            ", [$latitude, $longitude, $latitude])
            ->havingRaw('distance <= ?', [$radiusKm])
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
            ", [$listing->latitude, $listing->longitude, $listing->latitude])
            ->orderBy('distance', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->limit($limit)->get();
    }

    public function searchContractors(array $filters): LengthAwarePaginator
    {
        $query = User::query()
            ->where('user_type', 'contractor')
            ->where('is_active', true);

        if (!empty($filters['query'])) {
            $searchTerm = $filters['query'];
            
            $query->selectRaw("
                users.*,
                ts_rank(searchable, websearch_to_tsquery('english', ?)) as search_rank
            ", [$searchTerm])
            ->whereRaw("searchable @@ websearch_to_tsquery('english', ?)", [$searchTerm])
            ->orderByDesc('search_rank');
        }

        if (!empty($filters['city'])) {
            $query->where(function($q) use ($filters) {
                $q->where('city', 'ILIKE', '%' . $filters['city'] . '%')
                  ->orWhereRaw('city % ?', [$filters['city']]);
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    public function autocompleteServiceTypes(string $query, int $limit = 10): array
    {
        return ServiceListing::query()
            ->where('status', 'active')
            ->whereRaw('service_type % ?', [$query]) // Trigram similarity
            ->orWhere('service_type', 'ILIKE', $query . '%')
            ->selectRaw('service_type, COUNT(*) as count')
            ->groupBy('service_type')
            ->orderByRaw('similarity(service_type, ?) DESC', [$query])
            ->limit($limit)
            ->pluck('service_type')
            ->toArray();
    }

    public function autocompleteCities(string $query, int $limit = 10): array
    {
        return ServiceListing::query()
            ->where('status', 'active')
            ->whereRaw('service_city % ?', [$query])
            ->orWhere('service_city', 'ILIKE', $query . '%')
            ->selectRaw('service_city, COUNT(*) as count')
            ->groupBy('service_city')
            ->orderByRaw('similarity(service_city, ?) DESC', [$query])
            ->limit($limit)
            ->pluck('service_city')
            ->toArray();
    }
}