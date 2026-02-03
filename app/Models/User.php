<?php

namespace App\Models;

use App\Enums\UserType;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasUuids, HasApiTokens;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'user_type',
        'google_id',
        'apple_id',
        'is_active',
        'profile_photo',
        'bio',
        'city',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'full_name',
    ];

    protected function casts(): array
    {
        return [
            'user_type' => UserType::class,
            'is_active' => 'boolean',
            'email_verified_at' => 'datetime',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''))
        );
    }

    public function isClient(): bool
    {
        return $this->user_type === UserType::CLIENT;
    }

    public function isContractor(): bool
    {
        return $this->user_type === UserType::CONTRACTOR;
    }

    public function listings()
    {
        return $this->hasMany(ServiceListing::class, 'contractor_id');
    }

    public function pushTokens()
    {
        return $this->hasMany(PushToken::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function clientConversations()
    {
        return $this->hasMany(Conversation::class, 'client_id');
    }

    public function contractorConversations()
    {
        return $this->hasMany(Conversation::class, 'contractor_id');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function scopeClients($query)
    {
        return $query->where('user_type', UserType::CLIENT->value);
    }

    public function scopeContractors($query)
    {
        return $query->where('user_type', UserType::CONTRACTOR->value);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }
}
