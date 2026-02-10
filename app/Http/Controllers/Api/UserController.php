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

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Users', description: 'User management, search, and profile endpoints')]
class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}


    #[OA\Get(
        path: '/api/users',
        summary: 'Search and list all users',
        description: 'Search for users with advanced filtering capabilities. Supports full-text search across names, city, and bio. Filter by user type, location, and activity status.',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'query',
                in: 'query',
                description: 'Full-text search across first name, last name, city, and bio',
                required: false,
                schema: new OA\Schema(type: 'string', maxLength: 255),
                example: 'Jan Kowalski'
            ),
            new OA\Parameter(
                name: 'user_type',
                in: 'query',
                description: 'Filter by user type',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['client', 'contractor']),
                example: 'contractor'
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
                name: 'is_active',
                in: 'query',
                description: 'Filter by active status',
                required: false,
                schema: new OA\Schema(type: 'boolean'),
                example: true
            ),
            new OA\Parameter(
                name: 'has_listings',
                in: 'query',
                description: 'Filter contractors by whether they have active listings',
                required: false,
                schema: new OA\Schema(type: 'boolean'),
                example: true
            ),
            new OA\Parameter(
                name: 'search_mode',
                in: 'query',
                description: 'PostgreSQL text search mode',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['websearch', 'phrase', 'plainto']),
                example: 'websearch'
            ),
            new OA\Parameter(
                name: 'sort_by',
                in: 'query',
                description: 'Sort results',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['relevance', 'name', 'newest', 'city']),
                example: 'relevance'
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
                description: 'Users retrieved successfully',
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
                                    new OA\Property(property: 'user_type', type: 'string', enum: ['client', 'contractor']),
                                    new OA\Property(property: 'user_type_label', type: 'string', example: 'Wykonawca'),
                                    new OA\Property(property: 'profile_photo', type: 'string', nullable: true),
                                    new OA\Property(property: 'bio', type: 'string', nullable: true),
                                    new OA\Property(property: 'city', type: 'string', nullable: true),
                                    new OA\Property(property: 'address', type: 'string', nullable: true),
                                    new OA\Property(property: 'is_active', type: 'boolean'),
                                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'last_page', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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


    #[OA\Get(
        path: '/api/users/contractors',
        summary: 'List all contractors',
        description: 'Get a list of all contractor users with optional filtering',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'query',
                in: 'query',
                description: 'Search query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'city',
                in: 'query',
                description: 'Filter by city',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'has_listings',
                in: 'query',
                description: 'Filter by contractors with active listings',
                required: false,
                schema: new OA\Schema(type: 'boolean')
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
                description: 'Contractors retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
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


    #[OA\Get(
        path: '/api/users/clients',
        summary: 'List all clients',
        description: 'Get a list of all client users with optional filtering',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'query',
                in: 'query',
                description: 'Search query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'city',
                in: 'query',
                description: 'Filter by city',
                required: false,
                schema: new OA\Schema(type: 'string')
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
                description: 'Clients retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/users/{user}',
        summary: 'Get a specific user',
        description: 'Retrieve detailed information about a specific user by their UUID',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'User UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'first_name', type: 'string'),
                                new OA\Property(property: 'last_name', type: 'string'),
                                new OA\Property(property: 'full_name', type: 'string'),
                                new OA\Property(property: 'email', type: 'string'),
                                new OA\Property(property: 'phone_number', type: 'string', nullable: true),
                                new OA\Property(property: 'user_type', type: 'string'),
                                new OA\Property(property: 'user_type_label', type: 'string'),
                                new OA\Property(property: 'profile_photo', type: 'string', nullable: true),
                                new OA\Property(property: 'bio', type: 'string', nullable: true),
                                new OA\Property(property: 'city', type: 'string', nullable: true),
                                new OA\Property(property: 'address', type: 'string', nullable: true),
                                new OA\Property(property: 'is_active', type: 'boolean'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
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

    #[OA\Put(
        path: '/api/users/{user}',
        summary: 'Update user profile',
        description: 'Update a user profile. Users can only update their own profile.',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'User UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', maxLength: 255, example: 'Jan'),
                    new OA\Property(property: 'last_name', type: 'string', maxLength: 255, example: 'Kowalski'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
                    new OA\Property(property: 'phone_number', type: 'string', maxLength: 20, nullable: true),
                    new OA\Property(property: 'bio', type: 'string', maxLength: 1000, nullable: true),
                    new OA\Property(property: 'city', type: 'string', maxLength: 100, nullable: true),
                    new OA\Property(property: 'address', type: 'string', maxLength: 500, nullable: true),
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
                        new OA\Property(property: 'message', type: 'string', example: 'Profile updated successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized to update this profile'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/users/{user}/photo',
        summary: 'Upload/update profile photo',
        description: 'Upload or update a user\'s profile photo. Maximum 5MB. Supported formats: jpeg, jpg, png, webp.',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'User UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['photo'],
                    properties: [
                        new OA\Property(
                            property: 'photo',
                            type: 'string',
                            format: 'binary',
                            description: 'Profile photo (jpeg, jpg, png, webp, max 5MB)'
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
                        new OA\Property(property: 'message', type: 'string', example: 'Profile photo updated successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'profile_photo', type: 'string'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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

    #[OA\Delete(
        path: '/api/users/{user}/photo',
        summary: 'Delete profile photo',
        description: 'Delete a user\'s profile photo',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'User UUID',
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
                        new OA\Property(property: 'message', type: 'string', example: 'Profile photo deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized'),
            new OA\Response(response: 404, description: 'No photo to delete'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/users/{user}/deactivate',
        summary: 'Deactivate user account',
        description: 'Deactivate a user account (sets is_active to false)',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'User UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Account deactivated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Account deactivated successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/users/{user}/activate',
        summary: 'Activate user account',
        description: 'Activate a previously deactivated user account (sets is_active to true)',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'User UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Account activated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Account activated successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized'),
        ]
    )]
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

    #[OA\Delete(
        path: '/api/users/{user}',
        summary: 'Delete user account',
        description: 'Permanently delete a user account and all associated data',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'User UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Account deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Account deleted successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 403, description: 'Not authorized'),
        ]
    )]
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


    #[OA\Get(
        path: '/api/users/{user}/stats',
        summary: 'Get user statistics',
        description: 'Get statistics for a specific user (listings count, activity, etc.)',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                description: 'User UUID',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
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
                                new OA\Property(property: 'total_listings', type: 'integer', example: 15),
                                new OA\Property(property: 'active_listings', type: 'integer', example: 12),
                                new OA\Property(property: 'inactive_listings', type: 'integer', example: 3),
                                new OA\Property(property: 'member_since', type: 'string', format: 'date-time'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
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


    #[OA\Get(
        path: '/api/users/autocomplete',
        summary: 'User autocomplete search',
        description: 'Fast autocomplete search for users by name. Returns limited results for quick suggestions.',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'query',
                in: 'query',
                description: 'Search query (minimum 2 characters)',
                required: true,
                schema: new OA\Schema(type: 'string', minLength: 2),
                example: 'Jan'
            ),
            new OA\Parameter(
                name: 'user_type',
                in: 'query',
                description: 'Filter by user type',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['client', 'contractor'])
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Maximum number of results (default: 10, max: 50)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50),
                example: 10
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Autocomplete results',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'string', format: 'uuid'),
                                    new OA\Property(property: 'full_name', type: 'string', example: 'Jan Kowalski'),
                                    new OA\Property(property: 'user_type', type: 'string'),
                                    new OA\Property(property: 'city', type: 'string', nullable: true),
                                    new OA\Property(property: 'profile_photo', type: 'string', nullable: true),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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


    #[OA\Get(
        path: '/api/users/popular-cities',
        summary: 'Get popular cities',
        description: 'Get a list of cities with the most users, useful for location filters and suggestions',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'user_type',
                in: 'query',
                description: 'Filter cities by user type',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['client', 'contractor'])
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Number of cities to return (default: 20, max: 100)',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100),
                example: 20
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Popular cities retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'city', type: 'string', example: 'Warszawa'),
                                    new OA\Property(property: 'user_count', type: 'integer', example: 245),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
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
