<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Inquiry extends Model
{
    use HasUuids;

    protected $fillable = [
        'client_id',
        'contractor_id',
        'listing_id',
        'conversation_id',
        'initial_message',
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
}