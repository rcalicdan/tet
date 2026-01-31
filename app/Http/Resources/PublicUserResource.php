<?php

namespace App\Http\Resources;

use App\Enums\UserType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'user_type' => $this->user_type->value,
            'user_type_label' => $this->user_type->label(),
            'profile_photo' => $this->profile_photo 
                ? url('storage/' . $this->profile_photo) 
                : null,
            'city' => $this->city,
        ];

        if ($this->user_type === UserType::CONTRACTOR) {
            $data['bio'] = $this->bio;
            $data['phone_number'] = $this->phone_number;
            $data['listings_count'] = $this->whenLoaded('listings', function () {
                return $this->listings->count();
            }, $this->listings()->count());
        }

        return $data;
    }
}