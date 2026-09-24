<?php

namespace App\Http\Controllers\Api;

use App\AI\DTO\AgentRequest;
use App\AI\Services\AgentRouter;
use App\AI\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller;

class AiChatController extends Controller
{
    protected AgentRouter $router;
    protected ConversationService $conversationService;
    public function __construct(AgentRouter $router,ConversationService $conversationService) {
        $this->router = $router;
        $this->conversationService = $conversationService;
    }
    public function chat(Request $request): JsonResponse {
        $validated = $request->validate(['message' => ['required','string','min:1','max:10000',],
            'agent' => ['nullable','string','max:50',],
            'conversation_id' => ['nullable','integer','min:1',],
            'context' => ['nullable','array',],
            'metadata' => ['nullable','array',
            ],
        ]);
        try {
            $userId = Auth::id();
            $conversationId =isset($validated['conversation_id'])? (int) $validated['conversation_id']: null;
            if ($conversationId !== null) {
                $conversation =$this->conversationService->get($conversationId);
                if ($conversation === null) {
                    return response()->json(['success' => false,'message' => 'AI conversation not found.',
                        'agent' => $validated['agent'] ?? null,
                        'data' => [],
                        'sources' => [],
                        'metadata' => [],
                    ], 404);
                }

                $conversationUserId =
                    $conversation['user_id'] ?? null;

                if (
                    $userId !== null &&
                    $conversationUserId !== null &&
                    (int) $conversationUserId !==
                    (int) $userId
                ) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not allowed to use this conversation.',
                        'agent' => $validated['agent'] ?? null,
                        'data' => [],
                        'sources' => [],
                        'metadata' => [],
                    ], 403);
                }
            }

            
            $metadata =
                isset($validated['metadata']) &&
                is_array($validated['metadata'])
                ? $validated['metadata']
                : [];

            if ($conversationId !== null) {
                $metadata['conversation_id'] =
                    $conversationId;
            }

            $agentRequest = new AgentRequest(
                trim($validated['message']),
                $userId,
                isset($validated['agent'])
                    ? trim((string) $validated['agent'])
                    : null,
                isset($validated['context']) &&
                    is_array($validated['context'])
                    ? $validated['context']
                    : [],
                $metadata
            );

            $response = $this->router->route(
                $agentRequest
            );
            $responseData = $response->toArray();

            if (
                isset($responseData['metadata']) &&
                is_array($responseData['metadata']) &&
                isset(
                    $responseData['metadata']['conversation_id']
                )
            ) {
                $responseData['conversation_id'] =
                    $responseData['metadata']['conversation_id'];
            }

            return response()->json(
                $responseData,
                $response->isSuccessful()
                    ? 200
                    : 422
            );
        } catch (\Throwable $e) {
            Log::error(
                'AI chat request failed.',
                [
                    'user_id' => Auth::id(),
                    'agent' =>
                    $validated['agent'] ?? null,
                    'conversation_id' =>
                    $validated['conversation_id'] ?? null,
                    'message_length' =>
                    strlen(
                        (string)
                        $validated['message']
                    ),
                    'error' =>
                    $e->getMessage(),
                ]
            );

            return response()->json(
                [
                    'success' => false,
                    'message' =>
                    'The AI assistant could not process your request right now.',
                    'agent' =>
                    $validated['agent'] ?? null,
                    'data' => [],
                    'sources' => [],
                    'metadata' => [],
                ],
                500
            );
        }
    }

    
    public function agents(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'agents' => $this->router->agents(),
        ]);
    }

   
    public function checkAgent(
        string $agent
    ): JsonResponse {
        $supported =
            $this->router->supports(
                $agent
            );

        return response()->json([
            'success' => true,
            'agent' => $agent,
            'supported' => $supported,
        ]);
    }
}
