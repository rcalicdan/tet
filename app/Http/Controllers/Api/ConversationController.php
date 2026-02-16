<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(private ConversationService $conversationService)
    {
    }

    public function index(Request $request)
    {
        $conversations = $this->conversationService->getUserConversations($request->user());

        return response()->json([
            'success' => true,
            'data' => $conversations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contractor_id' => 'required|uuid|exists:users,id',
            'listing_id' => 'nullable|uuid|exists:service_listings,id',
        ]);

        $conversation = $this->conversationService->findOrCreateConversation(
            $request->user()->id,
            $validated['contractor_id'],
            $validated['listing_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => new ConversationResource($conversation),
        ], 201);
    }

    public function show(Request $request, Conversation $conversation)
    {
        if (!$conversation->hasParticipant($request->user()->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $this->conversationService->getConversationWithMessages($conversation);

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => new ConversationResource($conversation),
                'messages' => $data['messages'],
            ],
        ]);
    }
}