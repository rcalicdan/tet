<?php

namespace App\Http\Controllers\Api;

use App\Events\UserTyping;
use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Services\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private MessageService $messageService) {}

    public function index(Request $request, Conversation $conversation)
    {
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $messages = $this->messageService->getMessages($conversation);

        return MessageResource::collection($messages);
    }

    public function store(Request $request, Conversation $conversation)
    {
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'message_text' => 'required|string|max:5000',
        ]);

        $message = $this->messageService->sendMessage(
            $conversation,
            $request->user()->id,
            $validated['message_text']
        );

        return response()->json([
            'success' => true,
            'data' => new MessageResource($message),
        ], 201);
    }

    public function markAsRead(Request $request, Conversation $conversation)
    {
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $messageIds = $this->messageService->markMessagesAsRead(
            $conversation,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read',
            'data' => [
                'marked_count' => count($messageIds),
                'message_ids' => $messageIds,
            ],
        ]);
    }

    public function typing(Request $request, Conversation $conversation)
    {
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        broadcast(new UserTyping(
            $conversation->id,
            $request->user(),
            $validated['is_typing']
        ))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Typing status broadcasted',
        ]);
    }
}
