<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    
    if (!$conversation) {
        return false;
    }
    
    return $conversation->hasParticipant($user->id);
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return $user->id === $userId;
});