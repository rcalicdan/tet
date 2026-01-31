<?php

namespace App\Http\Controllers\Api;

use App\Factories\AuthServiceFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SocialLoginRequest;
use Illuminate\Http\JsonResponse;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    public function __construct(protected AuthServiceFactory $authFactory) {}

    public function login(SocialLoginRequest $request): JsonResponse
    {
        $provider = $request->input('provider');

        $service = $this->authFactory->make($provider);

        $result = $service->login($request->validated());

        return response()->json([
            'message' => ucfirst($provider) . ' login successful',
            'data' => $result
        ]);
    }

    public function redirectToProvider(string $provider)
    {
        if (!\in_array($provider, ['google', 'apple'])) {
            return response()->json(['error' => 'Provider not supported'], 400);
        }

        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback(string $provider): JsonResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
            $service = $this->authFactory->make($provider);

            $result = $service->loginWithSocialiteUser($socialUser);

            return response()->json([
                'message' => 'Login successful',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Authentication failed',
                'message' => $e->getMessage()
            ], 401);
        }
    }
}
