<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsVerification extends Model
{
    protected $fillable = [
        'phone_number',
        'code',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'attempts' => 'integer',
    ];
}