<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_type' => $this->service_type,
            'description' => $this->description,
            'price' => $this->price,
            'service_city' => $this->service_city,
            'service_radius_km' => $this->service_radius_km,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'contact_phone' => $this->contact_phone,
            'status' => $this->status,
            'contractor' => [
                'id' => $this->contractor->id,
                'full_name' => $this->contractor->full_name,
                'profile_photo' => $this->contractor->profile_photo,
                'city' => $this->contractor->city,
                'bio' => $this->contractor->bio,
            ],
            'photos' => ListingPhotoResource::collection($this->whenLoaded('photos')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}