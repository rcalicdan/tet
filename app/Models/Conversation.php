<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Conversation extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id',
        'contractor_id',
        'listing_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function contractor()
    {
        return $this->belongsTo(User::class, 'contractor_id');
    }

    public function listing()
    {
        return $this->belongsTo(ServiceListing::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function hasParticipant(string $userId): bool
    {
        return $this->client_id === $userId || $this->contractor_id === $userId;
    }

    public function getOtherParticipant(string $userId): User
    {
        return $this->client_id === $userId ? $this->contractor : $this->client;
    }
}