<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ServiceListingController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\UserController;
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

    Route::prefix('listings')->group(function () {
        Route::get('/', [ServiceListingController::class, 'index']);
        Route::get('/stats', [ServiceListingController::class, 'stats']);
        Route::get('/service-types', [ServiceListingController::class, 'serviceTypes']);
        Route::get('/cities', [ServiceListingController::class, 'cities']);
        Route::post('/', [ServiceListingController::class, 'store']);
        Route::get('/{listing}', [ServiceListingController::class, 'show']);
        Route::put('/{listing}', [ServiceListingController::class, 'update']);
        Route::delete('/{listing}', [ServiceListingController::class, 'destroy']);

        Route::post('/{listing}/photos', [ServiceListingController::class, 'uploadPhotos']);
        Route::delete('/{listing}/photos/{photo}', [ServiceListingController::class, 'deletePhoto']);
        Route::post('/{listing}/photos/reorder', [ServiceListingController::class, 'reorderPhotos']);

        Route::patch('/{listing}/toggle-status', [ServiceListingController::class, 'toggleStatus']);
    });

    Route::prefix('search')->group(function () {
        Route::get('/', [SearchController::class, 'search']);
        Route::get('/contractors', [SearchController::class, 'searchContractors']);
        Route::get('/popular-service-types', [SearchController::class, 'popularServiceTypes']);
        Route::get('/nearby', [SearchController::class, 'nearby']);
        Route::get('/similar/{listing}', [SearchController::class, 'similar']);
        Route::get('/autocomplete', [SearchController::class, 'autocomplete']);
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/contractors', [UserController::class, 'contractors']);
        Route::get('/clients', [UserController::class, 'clients']);
        Route::get('/autocomplete', [UserController::class, 'autocomplete']);
        Route::get('/popular-cities', [UserController::class, 'popularCities']);

        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::post('/{user}/photo', [UserController::class, 'updatePhoto']);
        Route::delete('/{user}/photo', [UserController::class, 'deletePhoto']);
        Route::post('/{user}/deactivate', [UserController::class, 'deactivate']);
        Route::post('/{user}/activate', [UserController::class, 'activate']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
        Route::get('/{user}/stats', [UserController::class, 'stats']);
    });
});
