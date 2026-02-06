<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchListingsRequest;
use App\Http\Resources\ServiceListingResource;
use App\Http\Resources\UserResource;
use App\Models\ServiceListing;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    public function search(SearchListingsRequest $request): JsonResponse
    {
        try {
            $results = $this->searchService->searchListings($request->validated());

            return response()->json([
                'success' => true,
                'data' => ServiceListingResource::collection($results),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search listings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function popularServiceTypes(): JsonResponse
    {
        try {
            $serviceTypes = $this->searchService->getPopularServiceTypes();

            return response()->json([
                'success' => true,
                'data' => $serviceTypes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get popular service types',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|integer|min:1|max:500',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $radiusKm = $request->radius_km ?? 10;
            $limit = $request->limit ?? 10;

            $listings = $this->searchService->getNearbyListings(
                $latitude,
                $longitude,
                $radiusKm,
                $limit
            );

            return response()->json([
                'success' => true,
                'data' => ServiceListingResource::collection($listings),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get nearby listings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function similar(ServiceListing $listing): JsonResponse
    {
        try {
            $similarListings = $this->searchService->getSimilarListings($listing);

            return response()->json([
                'success' => true,
                'data' => ServiceListingResource::collection($similarListings),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get similar listings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function searchContractors(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            $results = $this->searchService->searchContractors($request->all());

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($results),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'last_page' => $results->lastPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search contractors',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:service_types,cities',
            'query' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        try {
            $limit = $request->limit ?? 10;

            $suggestions = match ($request->type) {
                'service_types' => $this->searchService->autocompleteServiceTypes($request->query, $limit),
                'cities' => $this->searchService->autocompleteCities($request->query, $limit),
            };

            return response()->json([
                'success' => true,
                'data' => $suggestions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get autocomplete suggestions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
