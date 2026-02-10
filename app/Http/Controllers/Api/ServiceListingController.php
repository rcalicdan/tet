<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceListing\StoreServiceListingRequest;
use App\Http\Requests\ServiceListing\UpdateServiceListingRequest;
use App\Http\Requests\ServiceListing\IndexServiceListingRequest;
use App\Http\Resources\ServiceListingResource;
use App\Http\Resources\ListingPhotoResource;
use App\Models\ServiceListing;
use App\Models\ListingPhoto;
use App\Services\ServiceListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Service Listings', description: 'Service listing management and search endpoints')]
class ServiceListingController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ServiceListingService $listingService
    ) {}

    #[OA\Get(
        path: '/api/listings',
        summary: 'Search and filter service listings',
        description: 'Get all service listings with advanced search, filtering, and sorting capabilities. Supports full-text search, geographic filtering, and multiple filter combinations.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Full-text search across service type, description, and city. Supports partial matches and typos using PostgreSQL trigram similarity.',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 255),
                example: 'malarz'
            ),
            new OA\Parameter(
                name: 'service_type',
                in: 'query',
                description: 'Filter by exact service type',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 100),
                example: 'Malarz'
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filter by listing status. Defaults to "active" for public searches.',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'pending']),
                example: 'active'
            ),
            new OA\Parameter(
                name: 'city',
                in: 'query',
                description: 'Filter by city (case-insensitive partial match)',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 100),
                example: 'Warszawa'
            ),
            new OA\Parameter(
                name: 'contractor_id',
                in: 'query',
                description: 'Filter listings by specific contractor UUID',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'uuid'),
                example: '019c2ed6-23a8-733a-82d4-a51042aebb9b'
            ),
            new OA\Parameter(
                name: 'min_price',
                in: 'query',
                description: 'Minimum price filter',
                required: false,
                schema: new OA\Schema(type: 'number', format: 'float', minimum: 0),
                example: 100
            ),
            new OA\Parameter(
                name: 'max_price',
                in: 'query',
                description: 'Maximum price filter (must be >= min_price)',
                required: false,
                schema: new OA\Schema(type: 'number', format: 'float', minimum: 0),
                example: 500
            ),
            new OA\Parameter(
                name: 'latitude',
                in: 'query',
                description: 'Latitude for geographic search (requires longitude and optional radius_km)',
                required: false,
                schema: new OA\Schema(type: 'number', format: 'float', minimum: -90, maximum: 90),
                example: 52.2297
            ),
            new OA\Parameter(
                name: 'longitude',
                in: 'query',
                description: 'Longitude for geographic search (requires latitude and optional radius_km)',
                required: false,
                schema: new OA\Schema(type: 'number', format: 'float', minimum: -180, maximum: 180),
                example: 21.0122
            ),
            new OA\Parameter(
                name: 'radius_km',
                in: 'query',
                description: 'Search radius in kilometers (default: 50km, max: 500km)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 500),
                example: 50
            ),
            new OA\Parameter(
                name: 'sort_by',
                in: 'query',
                description: 'Sort results by specified field. When using search, results are automatically sorted by relevance. When using location search with sort_by=distance, results are sorted by proximity.',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['newest', 'oldest', 'price_asc', 'price_desc', 'service_type', 'city', 'distance']
                ),
                example: 'newest'
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
                description: 'Service listings retrieved successfully',
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
                                    new OA\Property(property: 'search_rank', type: 'number', description: 'Search relevance score (only present when using search parameter)', nullable: true),
                                    new OA\Property(property: 'distance', type: 'number', description: 'Distance in kilometers from search location (only present when using latitude/longitude)', nullable: true),
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
                                new OA\Property(property: 'from', type: 'integer', example: 1),
                                new OA\Property(property: 'to', type: 'integer', example: 15),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function index(IndexServiceListingRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ServiceListing::class);
        
        $listings = $this->listingService->getAllListings(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => ServiceListingResource::collection($listings),
            'meta' => [
                'current_page' => $listings->currentPage(),
                'last_page' => $listings->lastPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
                'from' => $listings->firstItem(),
                'to' => $listings->lastItem(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/listings/my-listings',
        summary: 'Get authenticated contractor\'s listings',
        description: 'Retrieve all listings belonging to the currently authenticated contractor. Supports same filters as the main listings endpoint.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'search',
                in: 'query',
                description: 'Search within your listings',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 255)
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filter by status',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'pending'])
            ),
            new OA\Parameter(
                name: 'service_type',
                in: 'query',
                description: 'Filter by service type',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'sort_by',
                in: 'query',
                description: 'Sort results',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['newest', 'oldest', 'price_asc', 'price_desc'])
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'My listings retrieved successfully',
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
                                new OA\Property(property: 'from', type: 'integer', example: 1),
                                new OA\Property(property: 'to', type: 'integer', example: 15),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
    public function myListings(IndexServiceListingRequest $request): JsonResponse
    {
        $listings = $this->listingService->getContractorListings(
            auth()->id(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data' => ServiceListingResource::collection($listings),
            'meta' => [
                'current_page' => $listings->currentPage(),
                'last_page' => $listings->lastPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
                'from' => $listings->firstItem(),
                'to' => $listings->lastItem(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/listings/stats',
        summary: 'Get contractor statistics',
        description: 'Get statistics for the authenticated contractor including total listings, status breakdown, and photo counts.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total', type: 'integer', example: 25),
                                new OA\Property(property: 'active', type: 'integer', example: 20),
                                new OA\Property(property: 'inactive', type: 'integer', example: 3),
                                new OA\Property(property: 'pending', type: 'integer', example: 2),
                                new OA\Property(property: 'total_photos', type: 'integer', example: 87),
                                new OA\Property(
                                    property: 'service_types',
                                    type: 'object',
                                    example: ['Malarz' => 10, 'Hydraulik' => 8, 'Elektryk' => 7]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->listingService->getContractorStats(auth()->id());

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/listings/service-types',
        summary: 'Get contractor\'s service types',
        description: 'Get a list of unique service types from the authenticated contractor\'s listings.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service types retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['Elektryk', 'Hydraulik', 'Malarz']
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function serviceTypes(): JsonResponse
    {
        try {
            $serviceTypes = $this->listingService->getContractorServiceTypes(auth()->id());

            return response()->json([
                'success' => true,
                'data' => $serviceTypes,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get service types',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/listings/cities',
        summary: 'Get contractor\'s cities',
        description: 'Get a list of unique cities from the authenticated contractor\'s listings.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cities retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            example: ['Gdańsk', 'Kraków', 'Warszawa']
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function cities(): JsonResponse
    {
        try {
            $cities = $this->listingService->getContractorCities(auth()->id());

            return response()->json([
                'success' => true,
                'data' => $cities,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get cities',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/listings',
        summary: 'Create a new service listing',
        description: 'Create a new service listing for the authenticated contractor.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['service_type', 'description', 'service_city', 'service_radius_km', 'contact_phone'],
                properties: [
                    new OA\Property(property: 'service_type', type: 'string', maxLength: 100, example: 'Malarz'),
                    new OA\Property(property: 'description', type: 'string', example: 'Profesjonalne usługi malarskie'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', nullable: true, example: 150.00),
                    new OA\Property(property: 'service_city', type: 'string', maxLength: 100, example: 'Warszawa'),
                    new OA\Property(property: 'service_radius_km', type: 'integer', minimum: 1, example: 30),
                    new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true, example: 52.2297),
                    new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true, example: 21.0122),
                    new OA\Property(property: 'contact_phone', type: 'string', example: '+48123456789'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Service listing created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Service listing created successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized to create listings'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function store(StoreServiceListingRequest $request): JsonResponse
    {
        $this->authorize('create', ServiceListing::class);

        try {
            $listing = $this->listingService->createListing(
                $request->validated(),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Service listing created successfully',
                'data' => new ServiceListingResource($listing),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create service listing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/listings/{listing}',
        summary: 'Get a single service listing',
        description: 'Retrieve detailed information about a specific service listing.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'listing',
                in: 'path',
                required: true,
                description: 'Service listing UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service listing retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized to view this listing'),
            new OA\Response(response: 404, description: 'Listing not found'),
        ]
    )]
    public function show(ServiceListing $listing): JsonResponse
    {
        $this->authorize('view', $listing);

        $listing->load(['photos', 'contractor']);

        return response()->json([
            'success' => true,
            'data' => new ServiceListingResource($listing),
        ]);
    }

    #[OA\Put(
        path: '/api/listings/{listing}',
        summary: 'Update a service listing',
        description: 'Update an existing service listing. Only the listing owner can update it.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'listing',
                in: 'path',
                required: true,
                description: 'Service listing UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'service_type', type: 'string', maxLength: 100),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'service_city', type: 'string', maxLength: 100),
                    new OA\Property(property: 'service_radius_km', type: 'integer', minimum: 1),
                    new OA\Property(property: 'latitude', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'longitude', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'contact_phone', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service listing updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Service listing updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized to update this listing'),
            new OA\Response(response: 404, description: 'Listing not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function update(UpdateServiceListingRequest $request, ServiceListing $listing): JsonResponse
    {
        $this->authorize('update', $listing);

        try {
            $listing = $this->listingService->updateListing(
                $listing,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Service listing updated successfully',
                'data' => new ServiceListingResource($listing),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service listing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Delete(
        path: '/api/listings/{listing}',
        summary: 'Delete a service listing',
        description: 'Permanently delete a service listing and all associated photos.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'listing',
                in: 'path',
                required: true,
                description: 'Service listing UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Service listing deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Service listing deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized to delete this listing'),
            new OA\Response(response: 404, description: 'Listing not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function destroy(ServiceListing $listing): JsonResponse
    {
        $this->authorize('delete', $listing);

        try {
            $this->listingService->deleteListing($listing);

            return response()->json([
                'success' => true,
                'message' => 'Service listing deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete service listing',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/listings/{listing}/photos',
        summary: 'Upload photos to a listing',
        description: 'Upload one or more photos to a service listing. Maximum 10 photos per listing.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'listing',
                in: 'path',
                required: true,
                description: 'Service listing UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['photos'],
                    properties: [
                        new OA\Property(
                            property: 'photos',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                            maxItems: 10,
                            description: 'Array of photos (jpeg, jpg, png, webp). Max 5MB each, max 10 photos per listing.'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Photos uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Photos uploaded successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'object')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized to manage photos for this listing'),
            new OA\Response(response: 404, description: 'Listing not found'),
            new OA\Response(response: 422, description: 'Validation error or maximum photos exceeded'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function uploadPhotos(Request $request, ServiceListing $listing): JsonResponse
    {
        $this->authorize('managePhotos', $listing);
        $request->validate([
            'photos' => 'required|array|max:10',
            'photos.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        try {
            $this->listingService->addPhotos($listing, $request->file('photos'));

            $listing->load('photos');

            return response()->json([
                'success' => true,
                'message' => 'Photos uploaded successfully',
                'data' => ListingPhotoResource::collection($listing->photos),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getMessage() === 'Maximum 10 photos allowed per listing' ? 422 : 500);
        }
    }

    #[OA\Delete(
        path: '/api/listings/{listing}/photos/{photo}',
        summary: 'Delete a photo from a listing',
        description: 'Delete a specific photo from a service listing.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'listing',
                in: 'path',
                required: true,
                description: 'Service listing UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
            new OA\Parameter(
                name: 'photo',
                in: 'path',
                required: true,
                description: 'Photo UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Photo deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Photo deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized'),
            new OA\Response(response: 404, description: 'Listing or photo not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function deletePhoto(ServiceListing $listing, ListingPhoto $photo): JsonResponse
    {
        $this->authorize('managePhotos', $listing);

        if ($photo->listing_id !== $listing->id) {
            return response()->json([
                'success' => false,
                'message' => 'Photo does not belong to this listing',
            ], 404);
        }

        try {
            $this->listingService->deletePhoto($photo);

            return response()->json([
                'success' => true,
                'message' => 'Photo deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete photo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/listings/{listing}/photos/reorder',
        summary: 'Reorder listing photos',
        description: 'Change the display order of photos in a listing.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'listing',
                in: 'path',
                required: true,
                description: 'Service listing UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['photo_order'],
                properties: [
                    new OA\Property(
                        property: 'photo_order',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'sort_order', type: 'integer', minimum: 0),
                            ]
                        ),
                        example: [
                            ['id' => '019c2ed6-23c9-708b-9b34-4fe289a6ca2b', 'sort_order' => 0],
                            ['id' => '019c2ed6-23cb-72f6-a0ca-3cc91899ef56', 'sort_order' => 1],
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Photos reordered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Photos reordered successfully'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized'),
            new OA\Response(response: 404, description: 'Listing not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function reorderPhotos(Request $request, ServiceListing $listing): JsonResponse
    {
        $this->authorize('managePhotos', $listing);

        $request->validate([
            'photo_order' => 'required|array',
            'photo_order.*.id' => 'required|uuid|exists:listing_photos,id',
            'photo_order.*.sort_order' => 'required|integer|min:0',
        ]);

        try {
            $this->listingService->reorderPhotos($listing, $request->photo_order);

            $listing->load('photos');

            return response()->json([
                'success' => true,
                'message' => 'Photos reordered successfully',
                'data' => ListingPhotoResource::collection($listing->photos),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder photos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[OA\Patch(
        path: '/api/listings/{listing}/toggle-status',
        summary: 'Toggle listing status',
        description: 'Toggle a listing between active and inactive status.',
        tags: ['Service Listings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'listing',
                in: 'path',
                required: true,
                description: 'Service listing UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status toggled successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Listing is now active'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'status', type: 'string', example: 'active'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized'),
            new OA\Response(response: 404, description: 'Listing not found'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function toggleStatus(ServiceListing $listing): JsonResponse
    {
        $this->authorize('toggleStatus', $listing);

        try {
            $newStatus = $this->listingService->toggleStatus($listing);

            return response()->json([
                'success' => true,
                'message' => "Listing is now {$newStatus}",
                'data' => ['status' => $newStatus],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle listing status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
