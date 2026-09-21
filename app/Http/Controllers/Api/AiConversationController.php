<?php

namespace App\Http\Controllers\Api;

use App\AI\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiConversationController extends Controller
{
    protected ConversationService $conversationService;

    public function __construct(
        ConversationService $conversationService
    ) {
        $this->conversationService = $conversationService;
    }

    /**
     * Create a new AI conversation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent' => [
                'nullable',
                'string',
                'max:50',
            ],
            'metadata' => [
                'nullable',
                'array',
            ],
        ]);

        try {
            $userId = Auth::id();

            /*
             * For now anonymous conversations are allowed.
             * When authentication is required later, this endpoint
             * can be protected with auth:sanctum.
             */
            $conversationId =
                $this->conversationService->start(
                    $userId,
                    isset($validated['agent'])
                        ? trim((string) $validated['agent'])
                        : null,
                    isset($validated['metadata']) &&
                        is_array($validated['metadata'])
                        ? $validated['metadata']
                        : []
                );

            if ($conversationId === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to create AI conversation.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'AI conversation created successfully.',
                'conversation_id' => $conversationId,
                'agent' => $validated['agent'] ?? null,
            ], 201);
        } catch (Throwable $e) {
            Log::error(
                'AI conversation creation failed.',
                [
                    'user_id' => Auth::id(),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to create AI conversation.',
            ], 500);
        }
    }

    /**
     * Show one AI conversation with its messages.
     */
    public function show(
        Request $request,
        int $conversationId
    ): JsonResponse {
        try {
            $conversation =
                $this->conversationService->get(
                    $conversationId
                );

            if ($conversation === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI conversation not found.',
                ], 404);
            }

            /*
             * Prevent a logged-in user from reading another
             * user's conversation.
             */
            if (
                Auth::check() &&
                isset($conversation['user_id']) &&
                $conversation['user_id'] !== null &&
                (int) $conversation['user_id'] !==
                (int) Auth::id()
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to access this conversation.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'conversation' => $conversation,
            ]);
        } catch (Throwable $e) {
            Log::error(
                'AI conversation retrieval failed.',
                [
                    'conversation_id' => $conversationId,
                    'user_id' => Auth::id(),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve AI conversation.',
            ], 500);
        }
    }

    /**
     * Return only messages from a conversation.
     */
    public function messages(
        Request $request,
        int $conversationId
    ): JsonResponse {
        $validated = $request->validate([
            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        try {
            $conversation =
                $this->conversationService->get(
                    $conversationId
                );

            if ($conversation === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI conversation not found.',
                ], 404);
            }

            if (
                Auth::check() &&
                isset($conversation['user_id']) &&
                $conversation['user_id'] !== null &&
                (int) $conversation['user_id'] !==
                (int) Auth::id()
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to access this conversation.',
                ], 403);
            }

            $limit =
                isset($validated['limit'])
                ? (int) $validated['limit']
                : null;

            $messages =
                $this->conversationService->messages(
                    $conversationId,
                    $limit
                );

            return response()->json([
                'success' => true,
                'conversation_id' => $conversationId,
                'messages' => $messages,
            ]);
        } catch (Throwable $e) {
            Log::error(
                'AI conversation messages retrieval failed.',
                [
                    'conversation_id' => $conversationId,
                    'user_id' => Auth::id(),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve conversation messages.',
            ], 500);
        }
    }

    /**
     * List recent conversations for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication is required to view conversations.',
            ], 401);
        }

        $validated = $request->validate([
            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        try {
            $limit =
                isset($validated['limit'])
                ? (int) $validated['limit']
                : 20;

            $conversations =
                $this->conversationService->userConversations(
                    (int) Auth::id(),
                    $limit
                );

            return response()->json([
                'success' => true,
                'conversations' => $conversations,
            ]);
        } catch (Throwable $e) {
            Log::error(
                'AI conversations listing failed.',
                [
                    'user_id' => Auth::id(),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve AI conversations.',
            ], 500);
        }
    }

    /**
     * Close an AI conversation.
     */
    public function close(
        Request $request,
        int $conversationId
    ): JsonResponse {
        try {
            $conversation =
                $this->conversationService->get(
                    $conversationId
                );

            if ($conversation === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI conversation not found.',
                ], 404);
            }

            if (
                Auth::check() &&
                isset($conversation['user_id']) &&
                $conversation['user_id'] !== null &&
                (int) $conversation['user_id'] !==
                (int) Auth::id()
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to modify this conversation.',
                ], 403);
            }

            $closed =
                $this->conversationService->close(
                    $conversationId
                );

            if (! $closed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to close AI conversation.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'AI conversation closed successfully.',
                'conversation_id' => $conversationId,
            ]);
        } catch (Throwable $e) {
            Log::error(
                'AI conversation closing failed.',
                [
                    'conversation_id' => $conversationId,
                    'user_id' => Auth::id(),
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Unable to close AI conversation.',
            ], 500);
        }
    }
}
