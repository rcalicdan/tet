<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Collection; 
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    public function getUserConversations(User $user): Collection
    {
        $cacheKey = "user_conversations_{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            return Conversation::where(function ($query) use ($user) {
                $query->where('client_id', $user->id)
                    ->orWhere('contractor_id', $user->id);
            })
                ->with(['latestMessage.sender', 'client', 'contractor', 'listing'])
                ->orderBy('last_message_at', 'desc')
                ->get()
                ->map(function ($conversation) use ($user) {
                    return [
                        'id' => $conversation->id,
                        'other_user' => $conversation->getOtherParticipant($user->id),
                        'listing' => $conversation->listing,
                        'last_message' => $conversation->latestMessage,
                        'unread_count' => $this->getUnreadCount($conversation, $user->id),
                        'last_message_at' => $conversation->last_message_at,
                    ];
                });
        });
    }

    public function findOrCreateConversation(
        string $userId,
        string $contractorId,
        ?string $listingId = null
    ): Conversation {
        $conversation = Conversation::where(function ($query) use ($userId, $contractorId) {
            $query->where('client_id', $userId)
                ->where('contractor_id', $contractorId);
        })
            ->orWhere(function ($query) use ($userId, $contractorId) {
                $query->where('client_id', $contractorId)
                    ->where('contractor_id', $userId);
            })
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'client_id' => $userId,
                'contractor_id' => $contractorId,
                'listing_id' => $listingId,
            ]);
        }

        $conversation->load(['client', 'contractor', 'listing']);

        $this->clearUserConversationsCache($userId);
        $this->clearUserConversationsCache($contractorId);

        return $conversation;
    }

    public function getConversationWithMessages(Conversation $conversation): array
    {
        $conversation->load(['client', 'contractor', 'listing', 'messages.sender']);

        return [
            'conversation' => $conversation,
            'messages' => $conversation->messages,
        ];
    }

    public function getUnreadCount(Conversation $conversation, string $userId): int
    {
        $cacheKey = "conversation_{$conversation->id}_unread_{$userId}";

        return Cache::remember($cacheKey, 60, function () use ($conversation, $userId) {
            return $conversation->messages()
                ->where('sender_id', '!=', $userId)
                ->where('is_read', false)
                ->count();
        });
    }

    public function clearUserConversationsCache(string $userId): void
    {
        Cache::forget("user_conversations_{$userId}");
    }

    public function clearUnreadCountCache(string $conversationId, string $userId): void
    {
        Cache::forget("conversation_{$conversationId}_unread_{$userId}");
    }
}