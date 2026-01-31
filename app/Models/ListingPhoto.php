<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ListingPhoto extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'listing_id',
        'photo_url',
        'sort_order',
        'uploaded_at'
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function listing()
    {
        return $this->belongsTo(ServiceListing::class);
    }
}
