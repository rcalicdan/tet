<?php

namespace App\Services\Auth;

use App\Interfaces\AuthInterface;
use App\Libraries\SmsApi;
use App\Models\User;
use App\Models\SmsVerification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Carbon\Carbon;

class SmsAuthService implements AuthInterface
{
    protected SmsApi $smsApi;
    
    public function __construct(SmsApi $smsApi)
    {
        $this->smsApi = $smsApi;
    }

    public function register(array $data): array
    {
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone_number' => $data['phone_number'],
            'user_type' => $data['user_type'],
            'provider_name' => 'sms',
            'is_active' => false, 
        ]);

        $this->sendVerificationCode($user->phone_number);

        return [
            'user' => $user,
            'message' => 'Verification code sent to your phone number',
            'phone_verified' => false,
        ];
    }

    public function sendVerificationCode(string $phoneNumber): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        SmsVerification::updateOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'code' => Hash::make($code),
                'expires_at' => Carbon::now()->addMinutes(10),
                'attempts' => 0,
            ]
        );

        $message = "Your verification code is: {$code}. Valid for 10 minutes.";

        $this->smsApi->sendMessage($phoneNumber, $message);
    }

    public function verifyCode(string $phoneNumber, string $code): bool
    {
        $verification = SmsVerification::where('phone_number', $phoneNumber)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$verification) {
            throw ValidationException::withMessages([
                'code' => ['Verification code has expired. Please request a new one.'],
            ]);
        }

        if ($verification->attempts >= 5) {
            throw ValidationException::withMessages([
                'code' => ['Too many failed attempts. Please request a new code.'],
            ]);
        }

        if (!Hash::check($code, $verification->code)) {
            $verification->increment('attempts');
            throw ValidationException::withMessages([
                'code' => ['Invalid verification code.'],
            ]);
        }

        $verification->update(['verified_at' => Carbon::now()]);

        return true;
    }

    public function completeRegistration(array $data): array
    {
        $user = User::where('phone_number', $data['phone_number'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'phone_number' => ['User not found.'],
            ]);
        }

        $verification = SmsVerification::where('phone_number', $data['phone_number'])
            ->whereNotNull('verified_at')
            ->first();

        if (!$verification) {
            throw ValidationException::withMessages([
                'phone_number' => ['Phone number not verified.'],
            ]);
        }

        $user->update([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        $verification->delete();

        $token = $user->createToken('Personal Access Token')->accessToken;

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'phone_verified' => true,
        ];
    }

    public function login(array $credentials): array
    {
        $phoneOrEmail = $credentials['login'];
        $password = $credentials['password'];

        $user = User::where('phone_number', $phoneOrEmail)
            ->orWhere('email', $phoneOrEmail)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'login' => ['Your account has been deactivated.'],
            ]);
        }

        $token = $user->createToken('Personal Access Token')->accessToken;

        return [
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function loginWithSocialiteUser(SocialiteUser $socialiteUser): array
    {
        throw new \Exception('Not supported for SMS authentication');
    }

    public function logout(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user) {
            $user->token()->revoke();
            return true;
        }

        return false;
    }
}