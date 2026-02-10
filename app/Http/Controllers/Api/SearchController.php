<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceListingResource;
use App\Http\Resources\UserResource;
use App\Models\ServiceListing;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Search', description: 'Search and discovery endpoints for listings, contractors, and suggestions')]
class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    #[OA\Get(
        path: '/api/search/contractors',
        summary: 'Search contractors',
        description: 'Search for contractors by name, city, or other criteria with pagination support',
        tags: ['Search'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'query',
                in: 'query',
                description: 'Search query for contractor name or bio',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 255),
                example: 'Jan Kowalski'
            ),
            new OA\Parameter(
                name: 'city',
                in: 'query',
                description: 'Filter by city',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 100),
                example: 'Warszawa'
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Number of results per page (default: 15, max: 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100),
                example: 15
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                description: 'Page number for pagination',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Contractors retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'first_name', type: 'string', example: 'Jan'),
                                    new OA\Property(property: 'last_name', type: 'string', example: 'Kowalski'),
                                    new OA\Property(property: 'full_name', type: 'string', example: 'Jan Kowalski'),
                                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                                    new OA\Property(property: 'phone_number', type: 'string', nullable: true),
                                    new OA\Property(property: 'user_type', type: 'string', example: 'contractor'),
                                    new OA\Property(property: 'user_type_label', type: 'string', example: 'Wykonawca'),
                                    new OA\Property(property: 'profile_photo', type: 'string', nullable: true),
                                    new OA\Property(property: 'bio', type: 'string', nullable: true),
                                    new OA\Property(property: 'city', type: 'string', nullable: true, example: 'Warszawa'),
                                    new OA\Property(property: 'address', type: 'string', nullable: true),
                                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'last_page', type: 'integer', example: 5),
                                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                                new OA\Property(property: 'total', type: 'integer', example: 72),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/search/popular-service-types',
        summary: 'Get popular service types',
        description: 'Retrieve the most popular service types based on listing count. Useful for trending services and category suggestions.',
        tags: ['Search'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Maximum number of service types to return (default: 20, max: 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100),
                example: 20
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Popular service types retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'service_type', type: 'string', example: 'Malarz'),
                                    new OA\Property(property: 'listing_count', type: 'integer', example: 156),
                                ]
                            ),
                            example: [
                                ['service_type' => 'Malarz', 'listing_count' => 156],
                                ['service_type' => 'Hydraulik', 'listing_count' => 143],
                                ['service_type' => 'Elektryk', 'listing_count' => 128],
                                ['service_type' => 'Stolarz', 'listing_count' => 98],
                                ['service_type' => 'Mechanik', 'listing_count' => 87],
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/search/nearby',
        summary: 'Find nearby service listings',
        description: 'Get service listings near a specific geographic location using latitude/longitude coordinates. Results are sorted by distance.',
        tags: ['Search'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'latitude',
                in: 'query',
                description: 'Latitude coordinate of the search location',
                required: true,
                schema: new OA\Schema(type: 'number', format: 'float', minimum: -90, maximum: 90),
                example: 52.2297
            ),
            new OA\Parameter(
                name: 'longitude',
                in: 'query',
                description: 'Longitude coordinate of the search location',
                required: true,
                schema: new OA\Schema(type: 'number', format: 'float', minimum: -180, maximum: 180),
                example: 21.0122
            ),
            new OA\Parameter(
                name: 'radius_km',
                in: 'query',
                description: 'Search radius in kilometers (default: 10, max: 500)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 500),
                example: 10
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Maximum number of listings to return (default: 10, max: 50)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50),
                example: 10
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Nearby listings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'service_type', type: 'string', example: 'Malarz'),
                                    new OA\Property(property: 'description', type: 'string'),
                                    new OA\Property(property: 'price', type: 'string', example: '487.10'),
                                    new OA\Property(property: 'service_city', type: 'string', example: 'Warszawa'),
                                    new OA\Property(property: 'service_radius_km', type: 'integer', example: 43),
                                    new OA\Property(property: 'latitude', type: 'string', example: '52.24370000'),
                                    new OA\Property(property: 'longitude', type: 'string', example: '20.94680000'),
                                    new OA\Property(property: 'contact_phone', type: 'string', example: '+48696902836'),
                                    new OA\Property(property: 'status', type: 'string', example: 'active'),
                                    new OA\Property(
                                        property: 'contractor',
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'full_name', type: 'string'),
                                            new OA\Property(property: 'profile_photo', type: 'string', nullable: true),
                                            new OA\Property(property: 'city', type: 'string', nullable: true),
                                            new OA\Property(property: 'bio', type: 'string', nullable: true),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: 'photos',
                                        type: 'array',
                                        items: new OA\Items(
                                            properties: [
                                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                                new OA\Property(property: 'photo_url', type: 'string'),
                                                new OA\Property(property: 'sort_order', type: 'integer'),
                                                new OA\Property(property: 'uploaded_at', type: 'string', format: 'date-time'),
                                            ]
                                        )
                                    ),
                                    new OA\Property(property: 'distance', type: 'number', description: 'Distance in kilometers from search location', example: 3.42),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/search/similar/{listing}',
        summary: 'Find similar listings',
        description: 'Get service listings similar to a specific listing based on service type, location, and other attributes. Useful for "related services" or "you might also like" features.',
        tags: ['Search'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'listing',
                in: 'path',
                required: true,
                description: 'Service listing UUID to find similar listings for',
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                example: '019c2ed6-23a8-733a-82d4-a51042aebb9b'
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Maximum number of similar listings to return (default: 10, max: 50)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50),
                example: 10
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Similar listings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'service_type', type: 'string', example: 'Malarz'),
                                    new OA\Property(property: 'description', type: 'string'),
                                    new OA\Property(property: 'price', type: 'string', example: '487.10'),
                                    new OA\Property(property: 'service_city', type: 'string', example: 'Warszawa'),
                                    new OA\Property(property: 'service_radius_km', type: 'integer', example: 43),
                                    new OA\Property(property: 'latitude', type: 'string'),
                                    new OA\Property(property: 'longitude', type: 'string'),
                                    new OA\Property(property: 'contact_phone', type: 'string'),
                                    new OA\Property(property: 'status', type: 'string', example: 'active'),
                                    new OA\Property(
                                        property: 'contractor',
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                            new OA\Property(property: 'full_name', type: 'string'),
                                            new OA\Property(property: 'profile_photo', type: 'string', nullable: true),
                                            new OA\Property(property: 'city', type: 'string', nullable: true),
                                            new OA\Property(property: 'bio', type: 'string', nullable: true),
                                        ]
                                    ),
                                    new OA\Property(
                                        property: 'photos',
                                        type: 'array',
                                        items: new OA\Items(type: 'object')
                                    ),
                                    new OA\Property(property: 'similarity_score', type: 'number', description: 'Similarity score (higher is more similar)', example: 0.85, nullable: true),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 404, description: 'Listing not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/search/autocomplete',
        summary: 'Autocomplete suggestions',
        description: 'Get autocomplete suggestions for service types or cities. Useful for search input fields with type-ahead functionality.',
        tags: ['Search'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'type',
                in: 'query',
                description: 'Type of autocomplete (service types or cities)',
                required: true,
                schema: new OA\Schema(type: 'string', enum: ['service_types', 'cities']),
                example: 'service_types'
            ),
            new OA\Parameter(
                name: 'query',
                in: 'query',
                description: 'Search query (minimum 2 characters)',
                required: true,
                schema: new OA\Schema(type: 'string', minLength: 2, maxLength: 100),
                example: 'mal'
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Maximum number of suggestions (default: 10, max: 20)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 20),
                example: 10
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Autocomplete suggestions retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['Malarz', 'Malarz elewacji', 'Malarz wnętrz']
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Not authenticated'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'The type field is required.'),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'type',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['The type field is required.']
                                ),
                                new OA\Property(
                                    property: 'query',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['The query field must be at least 2 characters.']
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
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