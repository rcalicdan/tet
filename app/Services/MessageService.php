<?php

namespace App\Services;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MessageService
{
    public function __construct(private ConversationService $conversationService) {}

    public function getMessages(Conversation $conversation, int $perPage = 50): LengthAwarePaginator
    {
        return $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function sendMessage(
        Conversation $conversation,
        string $senderId,
        string $messageText
    ): Message {
        return DB::transaction(function () use ($conversation, $senderId, $messageText) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $senderId,
                'message_text' => $messageText,
            ]);

            $conversation->update([
                'last_message_at' => now(),
            ]);

            $message->load('sender');

            $this->conversationService->clearUserConversationsCache($conversation->client_id);
            $this->conversationService->clearUserConversationsCache($conversation->contractor_id);

            broadcast(new MessageSent($message))->toOthers();

            return $message;
        });
    }

    public function markMessagesAsRead(Conversation $conversation, string $userId): array
    {
        $messageIds = $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->where('is_read', false)
            ->pluck('id')
            ->toArray();

        if (empty($messageIds)) {
            return [];
        }

        $conversation->messages()
            ->whereIn('id', $messageIds)
            ->update(['is_read' => true]);

        $this->conversationService->clearUnreadCountCache($conversation->id, $userId);

        broadcast(new MessageRead($conversation->id, $userId, $messageIds))->toOthers();

        return $messageIds;
    }
}
