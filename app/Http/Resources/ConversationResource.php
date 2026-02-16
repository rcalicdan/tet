<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'contractor_id' => $this->contractor_id,
            'listing_id' => $this->listing_id,
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'client' => [
                'id' => $this->client->id,
                'full_name' => $this->client->full_name,
                'profile_photo' => $this->client->profile_photo,
                'user_type' => $this->client->user_type->value,
            ],
            'contractor' => [
                'id' => $this->contractor->id,
                'full_name' => $this->contractor->full_name,
                'profile_photo' => $this->contractor->profile_photo,
                'user_type' => $this->contractor->user_type->value,
            ],
            'listing' => $this->listing ? [
                'id' => $this->listing->id,
                'title' => $this->listing->title,
            ] : null,
        ];
    }
}