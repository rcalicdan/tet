<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'message_text' => $this->message_text,
            'is_read' => $this->is_read,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'sender' => [
                'id' => $this->sender->id,
                'full_name' => $this->sender->full_name,
                'profile_photo' => $this->sender->profile_photo,
                'user_type' => $this->sender->user_type->value,
            ],
        ];
    }
}