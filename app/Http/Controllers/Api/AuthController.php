<?php

namespace App\Http\Controllers\Api;

use App\Factories\AuthServiceFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailLoginRequest;
use App\Http\Requests\Auth\EmailRegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth', description: 'Authentication endpoints')]
class AuthController extends Controller
{
    public function __construct(protected AuthServiceFactory $authFactory) {}

    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Register a new user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['first_name', 'last_name', 'email', 'password', 'password_confirmation', 'user_type'],
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', example: 'John'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'phone_number', type: 'string', example: '+48123456789'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'user_type', type: 'string', enum: ['client', 'contractor', 'both'], example: 'client'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User registered successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function register(EmailRegisterRequest $request): JsonResponse
    {
        $service = $this->authFactory->make('email');

        $data = $request->validated();

        $data['provider'] = 'email';

        $result = $service->register($data);

        return response()->json([
            'message' => 'User registered successfully',
            'data' => $result
        ], 201);
    }

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login with email and password',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ]
    )]
    public function login(EmailLoginRequest $request): JsonResponse
    {
        $service = $this->authFactory->make('email');

        $result = $service->login($request->validated());

        return response()->json([
            'message' => 'Login successful',
            'data' => $result
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout and revoke token',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successfully logged out',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Successfully logged out'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->token()->revoke();
            return response()->json(['message' => 'Successfully logged out']);
        }

        return response()->json(['message' => 'Not authenticated'], 401);
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Get authenticated user',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated user data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Not authenticated'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()
        ]);
    }
}