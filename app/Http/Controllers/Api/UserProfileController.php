<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Requests\Profile\UploadPhotoRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\PublicUserResource;
use App\Services\UserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Profile', description: 'User profile endpoints')]
class UserProfileController extends Controller
{
    public function __construct(
        private UserProfileService $profileService
    ) {}

    #[OA\Get(
        path: '/api/profile',
        summary: 'Get authenticated user profile',
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User profile data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => new UserResource($user)
        ]);
    }

    #[OA\Put(
        path: '/api/profile',
        summary: 'Update authenticated user profile',
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', example: 'John'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'phone_number', type: 'string', example: '+48123456789'),
                    new OA\Property(property: 'bio', type: 'string', nullable: true, example: 'Developer from Warsaw'),
                    new OA\Property(property: 'city', type: 'string', nullable: true, example: 'Warsaw'),
                    new OA\Property(property: 'address', type: 'string', nullable: true, example: 'ul. Marszałkowska 1, 00-639'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Profil zaktualizowany pomyślnie'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = $this->profileService->updateProfile(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Profil zaktualizowany pomyślnie',
                'data' => new UserResource($user)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Błąd podczas aktualizacji profilu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Post(
        path: '/api/profile/photo',
        summary: 'Upload profile photo',
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['profile_photo'],
                    properties: [
                        new OA\Property(
                            property: 'profile_photo',
                            type: 'string',
                            format: 'binary',
                            description: 'Profile photo (jpeg, png, jpg, gif, webp). Max 10MB.'
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Photo uploaded successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Zdjęcie profilowe zaktualizowane pomyślnie'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function updatePhoto(UploadPhotoRequest $request): JsonResponse
    {
        try {
            $user = $this->profileService->uploadProfilePhoto(
                $request->user(),
                $request->file('profile_photo')
            );

            return response()->json([
                'success' => true,
                'message' => 'Zdjęcie profilowe zaktualizowane pomyślnie',
                'data' => [
                    'profile_photo' => $this->profileService->getProfilePhotoUrl($user->profile_photo)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Błąd podczas przesyłania zdjęcia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Delete(
        path: '/api/profile/photo',
        summary: 'Delete profile photo',
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Photo deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Zdjęcie profilowe usunięte pomyślnie'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function deletePhoto(Request $request): JsonResponse
    {
        try {
            $this->profileService->deleteProfilePhoto($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Zdjęcie profilowe usunięte pomyślnie'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Błąd podczas usuwania zdjęcia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/profile/{id}',
        summary: 'Get public user profile by ID',
        tags: ['Profile'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
                example: '123e4567-e89b-12d3-a456-426614174000'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Public user profile',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'User not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Użytkownik nie został znaleziony'),
                    ]
                )
            ),
        ]
    )]
    public function showById(string $id, Request $request): JsonResponse
    {
        $user = $this->profileService->getPublicProfile($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Użytkownik nie został znaleziony'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new PublicUserResource($user)
        ]);
    }

    #[OA\Delete(
        path: '/api/profile/deactivate',
        summary: 'Deactivate user account',
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Account deactivated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Konto zostało dezaktywowane pomyślnie'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function deactivate(Request $request): JsonResponse
    {
        try {
            $this->profileService->deactivateAccount($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Konto zostało dezaktywowane pomyślnie'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Błąd podczas dezaktywacji konta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Delete(
        path: '/api/profile',
        summary: 'Permanently delete user account',
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Account deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Konto zostało usunięte pomyślnie'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        try {
            $this->profileService->deleteAccount($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Konto zostało usunięte pomyślnie'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Błąd podczas usuwania konta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/profile/completion',
        summary: 'Get profile completion status',
        tags: ['Profile'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile completion data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
    public function completion(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => 'true',
            'data' => [
                'is_complete' => $this->profileService->isProfileComplete($user),
                'completion_percentage' => $this->profileService->getProfileCompletionPercentage($user)
            ]
        ]);
    }
}
