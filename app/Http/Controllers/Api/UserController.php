<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\SearchUsersRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    public function index(SearchUsersRequest $request): JsonResponse
    {
        try {
            $users = $this->userService->searchUsers($request->validated());

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($users),
                'meta' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function contractors(SearchUsersRequest $request): JsonResponse
    {
        try {
            $contractors = $this->userService->getContractors($request->validated());

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($contractors),
                'meta' => [
                    'current_page' => $contractors->currentPage(),
                    'last_page' => $contractors->lastPage(),
                    'per_page' => $contractors->perPage(),
                    'total' => $contractors->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch contractors',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function clients(SearchUsersRequest $request): JsonResponse
    {
        try {
            $clients = $this->userService->getClients($request->validated());

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($clients),
                'meta' => [
                    'current_page' => $clients->currentPage(),
                    'last_page' => $clients->lastPage(),
                    'per_page' => $clients->perPage(),
                    'total' => $clients->total(),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch clients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(User $user): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($user->id);

            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $user = $this->userService->updateUser($user, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => new UserResource($user),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updatePhoto(Request $request, User $user): JsonResponse
    {
        if (auth()->id() !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        try {
            $user = $this->userService->updateProfilePhoto($user, $request->file('photo'));

            return response()->json([
                'success' => true,
                'message' => 'Profile photo updated successfully',
                'data' => new UserResource($user),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile photo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deletePhoto(User $user): JsonResponse
    {
        if (auth()->id() !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $user = $this->userService->deleteProfilePhoto($user);

            return response()->json([
                'success' => true,
                'message' => 'Profile photo deleted successfully',
                'data' => new UserResource($user),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete profile photo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deactivate(User $user): JsonResponse
    {
        if (auth()->id() !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $user = $this->userService->deactivateUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Account deactivated successfully',
                'data' => new UserResource($user),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate account',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function activate(User $user): JsonResponse
    {
        if (auth()->id() !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $user = $this->userService->activateUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Account activated successfully',
                'data' => new UserResource($user),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate account',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        if (auth()->id() !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->userService->deleteUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function stats(User $user): JsonResponse
    {
        try {
            $stats = $this->userService->getUserStats($user);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch user stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'user_type' => 'nullable|in:client,contractor',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $results = $this->userService->autocompleteUsers(
                $request->query,
                $request->user_type,
                $request->limit ?? 10
            );

            return response()->json([
                'success' => true,
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to autocomplete users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function popularCities(): JsonResponse
    {
        try {
            $cities = $this->userService->getPopularCities();

            return response()->json([
                'success' => true,
                'data' => $cities,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch popular cities',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}