<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ServiceListing extends Model
{
    use HasUuids;

    protected $fillable = [
        'contractor_id',
        'service_type',
        'description',
        'price',
        'service_city',
        'service_radius_km',
        'latitude',
        'longitude',
        'contact_phone',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'service_radius_km' => 'integer',
    ];

    public function contractor()
    {
        return $this->belongsTo(User::class, 'contractor_id');
    }

    public function photos()
    {
        return $this->hasMany(ListingPhoto::class, 'listing_id')->orderBy('sort_order');
    }
}
