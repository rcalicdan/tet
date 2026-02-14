<?php

namespace App\Factories;

use App\Interfaces\AuthInterface;
use App\Services\Auth\AppleAuthService;
use App\Services\Auth\EmailAuthService;
use App\Services\Auth\GoogleAuthService;
use App\Services\Auth\SmsAuthService;
use InvalidArgumentException;

class AuthServiceFactory
{
    public function make(string $provider): AuthInterface
    {
        return match ($provider) {
            'email' => app(EmailAuthService::class),
            'google' => app(GoogleAuthService::class),
            'apple' => app(AppleAuthService::class),
            'sms' => app(SmsAuthService::class),
            default => throw new InvalidArgumentException("Unsupported authentication provider: {$provider}"),
        };
    }
}