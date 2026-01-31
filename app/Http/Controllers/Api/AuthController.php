<?php

namespace App\Http\Controllers\Api;

use App\Factories\AuthServiceFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailLoginRequest;
use App\Http\Requests\Auth\EmailRegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthServiceFactory $authFactory) {}

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

    public function login(EmailLoginRequest $request): JsonResponse
    {
        $service = $this->authFactory->make('email');

        $result = $service->login($request->validated());

        return response()->json([
            'message' => 'Login successful',
            'data' => $result
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $user->token()->revoke();
            return response()->json(['message' => 'Successfully logged out']);
        }

        return response()->json(['message' => 'Not authenticated'], 401);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user()
        ]);
    }
}
