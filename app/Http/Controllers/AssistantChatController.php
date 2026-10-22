<?php

namespace App\Http\Controllers;

use App\AI\DTO\AgentRequest;
use App\AI\Services\AgentRouter;
use Illuminate\Http\Request;
use Throwable;

// Site-wide chat widget. Runs in the web session so answers can use the visitor's saved money profile;
// a visitor can only continue conversations started in their own session.
class AssistantChatController extends Controller
{
    public function chat(Request $r, AgentRouter $router)
    {
        $d = $r->validate(['message' => 'required|string|max:2000', 'conversation_id' => 'nullable|integer', 'page' => 'nullable|string|max:200']);
        $owned = (array) session('assistant_conversations', []);
        $cid = isset($d['conversation_id']) && in_array((int) $d['conversation_id'], $owned, true) ? (int) $d['conversation_id'] : null;
        try {
            $res = $router->route(new AgentRequest(trim($d['message']), $r->user()?->id, null, ['current_page' => $d['page'] ?? null], $cid ? ['conversation_id' => $cid] : []));
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'The assistant is unavailable right now. Please try again.'], 503);
        }
        $newId = $res->metadata['conversation_id'] ?? null;
        if ($newId && !in_array((int) $newId, $owned, true)) session(['assistant_conversations' => array_slice(array_merge($owned, [(int) $newId]), -20)]);
        $text = $res->success ? $res->message : 'I could not answer that right now. Try asking about jobs, companies, salary, tax, mutual funds or retirement planning.';
        return response()->json(['message' => $text, 'agent' => $res->agent ?? null, 'links' => $res->data['links'] ?? [], 'conversation_id' => $newId]);
    }
}
