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

class UserProfileController extends Controller
{
    public function __construct(
        private UserProfileService $profileService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => new UserResource($user)
        ]);
    }

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

    public function completion(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'is_complete' => $this->profileService->isProfileComplete($user),
                'completion_percentage' => $this->profileService->getProfileCompletionPercentage($user)
            ]
        ]);
    }
}