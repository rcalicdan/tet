<?php

namespace App\Services\Auth;

use App\Interfaces\AuthInterface;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

class GoogleAuthService implements AuthInterface
{
    public function register(array $data): array
    {
        return $this->handleSocialAuth($data);
    }

    public function login(array $credentials): array
    {
        return $this->handleSocialAuth($credentials);
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

    public function loginWithSocialiteUser(SocialiteUser $socialiteUser): array
    {
        $googleUser = (object) [
            'id' => $socialiteUser->getId(),
            'email' => $socialiteUser->getEmail(),
            'name' => $socialiteUser->getName(),
            'avatar' => $socialiteUser->getAvatar(),
        ];

        $user = $this->findOrCreateUser($googleUser, ['user_type' => 'client']); 

        $tokenResult = $user->createToken('Personal Access Token');

        return [
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
        ];
    }

    protected function handleSocialAuth(array $data): array
    {
        $token = $data['access_token'];
        $googleUser = null;

        try {
            $client = new GoogleClient(['client_id' => config('services.google.client_id')]);
            
            $payload = $client->verifyIdToken($token);

            if ($payload) {
                $googleUser = (object) [
                    'id' => $payload['sub'],
                    'email' => $payload['email'],
                    'name' => $payload['name'] ?? '',
                    'avatar' => $payload['picture'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            // If this fails, it might be an Access Token (Web), so we ignore and try Attempt 2
        }

        if (! $googleUser) {
            try {
                $socialiteUser = Socialite::driver('google')
                    ->stateless()
                    ->userFromToken($token);

                $googleUser = (object) [
                    'id' => $socialiteUser->getId(),
                    'email' => $socialiteUser->getEmail(),
                    'name' => $socialiteUser->getName(),
                    'avatar' => $socialiteUser->getAvatar(),
                ];
            } catch (\Exception $e) {
                Log::error('Google auth failed: ' . $e->getMessage());
                throw ValidationException::withMessages([
                    'access_token' => ['Invalid Google token. Please try again.'],
                ]);
            }
        }

        $user = $this->findOrCreateUser($googleUser, $data);

        $tokenResult = $user->createToken('Personal Access Token');

        return [
            'user' => $user,
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
        ];
    }

    protected function findOrCreateUser($socialUser, array $additionalData): User
    {
        $user = User::where('provider_name', 'google')
            ->where('provider_id', $socialUser->id)
            ->first();

        if ($user) {
            return $user;
        }

        $existingUser = User::where('email', $socialUser->email)->first();
        if ($existingUser) {
            $existingUser->update([
                'provider_name' => 'google',
                'provider_id' => $socialUser->id,
            ]);
            return $existingUser;
        }

        $names = $this->parseFullName($socialUser->name);
        
        return User::create([
            'first_name' => $additionalData['first_name'] ?? $names['first_name'],
            'last_name' => $additionalData['last_name'] ?? $names['last_name'],
            'email' => $socialUser->email,
            'user_type' => $additionalData['user_type'] ?? 'client', // Default or from request
            'provider_name' => 'google',
            'provider_id' => $socialUser->id,
            'is_active' => true,
            'password' => bcrypt(str()->random(24)), // Secure random password
        ]);
    }

    protected function parseFullName(?string $fullName): array
    {
        if (!$fullName) {
            return ['first_name' => 'User', 'last_name' => ''];
        }
        $parts = explode(' ', trim($fullName), 2);
        return [
            'first_name' => $parts[0] ?? 'User',
            'last_name' => $parts[1] ?? '',
        ];
    }
}