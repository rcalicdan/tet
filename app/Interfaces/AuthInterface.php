<?php

namespace App\Interfaces;

use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

interface AuthInterface
{
    public function register(array $data): array;

    public function login(array $credentials): array;

    public function logout(): bool;

    public function loginWithSocialiteUser(SocialiteUser $socialiteUser): array;
}