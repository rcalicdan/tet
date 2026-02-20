<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

function createUser(array $attributes = []): App\Models\User
{
    return App\Models\User::factory()->create($attributes);
}

function authenticatedUser(array $attributes = []): array
{
    $user = createUser($attributes);
    $token = $user->createToken('TestToken')->accessToken;

    return [
        'user' => $user,
        'token' => $token,
        'headers' => [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ],
    ];
}
