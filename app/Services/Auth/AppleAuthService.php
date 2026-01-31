<?php

namespace App\Services\Auth;

use App\Interfaces\AuthInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class AppleAuthService implements AuthInterface
{
    public function register(array $data): array
    {
        return $this->handleSocialAuth($data);
    }

    public function login(array $credentials): array
    {
        return $this->handleSocialAuth($credentials);
    }

    public function loginWithSocialiteUser(SocialiteUser $socialiteUser): array
    {
       throw new \Exception('Not Yet Implemented');
    }

    public function logout(): bool
    {
        $user = Auth::user();
        
        if ($user) {
            $user->token()->revoke();
            return true;
        }

        return false;
    }

    protected function handleSocialAuth(array $data): array
    {
        try {
            $socialUser = Socialite::driver('apple')
                ->stateless()
                ->userFromToken($data['access_token']);

            $user = $this->findOrCreateUser($socialUser, $data);

            $token = $user->createToken('Personal Access Token')->accessToken;

            return [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ];

        } catch (\Exception $e) {
            Log::error('Apple authentication failed: ' . $e->getMessage());
            throw ValidationException::withMessages([
                'access_token' => ['Failed to authenticate with Apple. Please verify your access token.'],
            ]);
        }
    }

    protected function findOrCreateUser($socialUser, array $additionalData): User
    {
        $user = User::where('provider_name', 'apple')
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($user) {
            return $user;
        }

        $email = $socialUser->getEmail();
        
        if ($email) {
            $existingUser = User::where('email', $email)->first();
            
            if ($existingUser) {
                $existingUser->provider_name = 'apple';
                $existingUser->provider_id = $socialUser->getId();
                $existingUser->save();
                return $existingUser;
            }
        }

        if (!isset($additionalData['user_type'])) {
            throw ValidationException::withMessages([
                'user_type' => ['User type is required for registration.'],
            ]);
        }

        $firstName = $additionalData['first_name'] ?? $socialUser->getName() ?? 'Apple';
        $lastName = $additionalData['last_name'] ?? 'User';

        return User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email, 
            'user_type' => $additionalData['user_type'],
            'provider_name' => 'apple',
            'provider_id' => $socialUser->getId(),
            'is_active' => true,
        ]);
    }
}