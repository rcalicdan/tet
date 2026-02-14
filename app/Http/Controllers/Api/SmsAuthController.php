<?php

namespace App\Http\Controllers\Api;

use App\Factories\AuthServiceFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SmsCompleteRegistrationRequest;
use App\Http\Requests\Auth\SmsLoginRequest;
use App\Http\Requests\Auth\SmsRegisterRequest;
use App\Http\Requests\Auth\SmsResendRequest;
use App\Http\Requests\Auth\SmsVerifyRequest;
use App\Services\Auth\SmsAuthService;
use Illuminate\Http\JsonResponse;

class SmsAuthController extends Controller
{
    public function __construct(
        protected AuthServiceFactory $authFactory,
        protected SmsAuthService $smsAuthService
    ) {}


    public function register(SmsRegisterRequest $request): JsonResponse
    {
        $service = $this->authFactory->make('sms');
        
        $result = $service->register($request->validated());

        return response()->json([
            'message' => 'Registration initiated. Please verify your phone number.',
            'data' => $result
        ], 201);
    }

    public function verify(SmsVerifyRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $this->smsAuthService->verifyCode(
            $validated['phone_number'],
            $validated['code']
        );

        return response()->json([
            'message' => 'Phone number verified successfully',
            'data' => [
                'verified' => true
            ]
        ]);
    }

    public function completeRegistration(SmsCompleteRegistrationRequest $request): JsonResponse
    {
        $result = $this->smsAuthService->completeRegistration($request->validated());

        return response()->json([
            'message' => 'Registration completed successfully',
            'data' => $result
        ], 201);
    }

    public function resend(SmsResendRequest $request): JsonResponse
    {
        $this->smsAuthService->sendVerificationCode($request->input('phone_number'));

        return response()->json([
            'message' => 'Verification code sent successfully'
        ]);
    }

    public function login(SmsLoginRequest $request): JsonResponse
    {
        $service = $this->authFactory->make('sms');
        
        $result = $service->login($request->validated());

        return response()->json([
            'message' => 'Login successful',
            'data' => $result
        ]);
    }
}