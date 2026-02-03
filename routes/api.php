<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::prefix('social')->group(function () {
        Route::post('login', [SocialAuthController::class, 'login']);
        Route::get('{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
        Route::get('{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verify'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::post('email/verify/resend', [AuthController::class, 'resend'])
            ->middleware('throttle:6,1')
            ->name('verification.resend');

        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::prefix('profile')->group(function () {
        Route::get('/', [UserProfileController::class, 'show']);
        Route::put('/', [UserProfileController::class, 'update']);
        Route::post('photo', [UserProfileController::class, 'updatePhoto']);
        Route::delete('photo', [UserProfileController::class, 'deletePhoto']);
        Route::post('deactivate', [UserProfileController::class, 'deactivate']);
        Route::delete('/', [UserProfileController::class, 'destroy']);
    });

    Route::get('users/{id}/profile', [UserProfileController::class, 'showById']);
});
