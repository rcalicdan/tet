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

class ServiceListingController extends Controller
{
    public function __construct(
        protected ServiceListingService $listingService
    ) {}

    public function index(IndexServiceListingRequest $request): JsonResponse
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

    public function store(StoreServiceListingRequest $request): JsonResponse
    {
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

    public function show(ServiceListing $listing): JsonResponse
    {
        $listing->load(['photos', 'contractor']);

        return response()->json([
            'success' => true,
            'data' => new ServiceListingResource($listing),
        ]);
    }

    public function update(UpdateServiceListingRequest $request, ServiceListing $listing): JsonResponse
    {
        if (!$this->listingService->authorizeContractor($listing, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this listing',
            ], 403);
        }

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

    public function destroy(ServiceListing $listing): JsonResponse
    {
        if (!$this->listingService->authorizeContractor($listing, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this listing',
            ], 403);
        }

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

    public function uploadPhotos(Request $request, ServiceListing $listing): JsonResponse
    {
        if (!$this->listingService->authorizeContractor($listing, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to upload photos to this listing',
            ], 403);
        }

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

    public function deletePhoto(ServiceListing $listing, ListingPhoto $photo): JsonResponse
    {
        if (!$this->listingService->authorizeContractor($listing, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this photo',
            ], 403);
        }

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

    public function reorderPhotos(Request $request, ServiceListing $listing): JsonResponse
    {
        if (!$this->listingService->authorizeContractor($listing, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to reorder photos for this listing',
            ], 403);
        }

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

    public function toggleStatus(ServiceListing $listing): JsonResponse
    {
        if (!$this->listingService->authorizeContractor($listing, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to change this listing status',
            ], 403);
        }

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