<?php

namespace App\AI\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ConversationService
{

    public function start(?int $userId = null,?string $agent = null,array $metadata = []): ?int {
        if (! $this->tableExists('ai_conversations')) {return null;}
        try {
            $data = ['user_id' => $userId,'agent' => $agent,'status' => 'active','metadata' => $this->encodeJson($metadata),'created_at' => now(),'updated_at' => now(),];
            $data = $this->filterColumns('ai_conversations',$data);
            return (int) DB::table('ai_conversations')->insertGetId($data);
        } catch (Throwable $e) {
            report($e);
            return null;
        }
    }
    public function addMessage(int $conversationId,string $role,string $content,array $metadata = []): ?int {
        if (! $this->tableExists('ai_messages')) {
            return null;
        }
        try {
            $data = [
                'conversation_id' => $conversationId,
                'role' => $role,
                'message' => $content,
                'agent' => $metadata['agent'] ?? null,
                'payload' => $this->encodeJson($metadata['payload'] ?? []),
                'sources' => $this->encodeJson($metadata['sources'] ?? []),
                'metadata' => $this->encodeJson($metadata),'user_id' => $metadata['user_id'] ?? null,'created_at' => now(),'updated_at' => now(),];
            $data = $this->filterColumns('ai_messages',$data);
            return (int) DB::table('ai_messages')->insertGetId($data);
        } catch (Throwable $e) {
            report($e);
            return null;
        }
    }
    public function addUserMessage(int $conversationId,string $content,array $metadata = []): ?int {
        return $this->addMessage($conversationId,'user',$content,$metadata);
    }
    public function addAssistantMessage(int $conversationId,string $content,array $metadata = []): ?int {
        return $this->addMessage($conversationId,'assistant',$content,$metadata);
    }
    public function get(int $conversationId): ?array {
        if (! $this->tableExists('ai_conversations')) {return null;}
        try {
            $conversation = DB::table('ai_conversations')->where('id', $conversationId)->first();
            if (! $conversation) {return null;}
            $conversation = (array) $conversation;
            if (isset($conversation['context'])) {$conversation['context'] =$this->decodeJson($conversation['context']);}
            if (isset($conversation['metadata'])) {
                $conversation['metadata'] =$this->decodeJson($conversation['metadata']);
            }
            $conversation['messages'] =$this->messages($conversationId);
            return $conversation;
        } catch (Throwable $e) {
            report($e);
            return null;
        }
    }
    public function messages(int $conversationId,?int $limit = null): array {
        if (! $this->tableExists('ai_messages')) {return [];}
        try {
            $query = DB::table('ai_messages')->where('conversation_id',$conversationId)->orderBy('id');
            if ($limit !== null && $limit > 0) {$query->limit($limit);}
            return $query->get()->map(function ($message) {
                    $message = (array) $message;
                    if (isset($message['message']) && ! isset($message['content'])) {
                        $message['content'] =$message['message'];
                    }
                    if (isset($message['payload'])) {$message['payload'] =$this->decodeJson($message['payload']);}
                    if (isset($message['sources'])) {$message['sources'] =$this->decodeJson($message['sources']);}
                    if (isset($message['metadata'])) {$message['metadata'] =$this->decodeJson($message['metadata']);}
                    return $message;
                })->all();
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Get recent conversations for a user.
     */
    public function userConversations(
        int $userId,
        ?int $limit = 20
    ): array {
        if (! $this->tableExists('ai_conversations')) {return [];}
        try {
            $query = DB::table('ai_conversations')->where('user_id',$userId)->orderByDesc('id');
            if ($limit !== null && $limit > 0) {$query->limit($limit);}
            return $query->get()->map(function ($conversation) {
                    $conversation = (array) $conversation;
                    if (isset($conversation['context'])) {
                        $conversation['context'] =$this->decodeJson($conversation['context']);
                    }
                    if (isset($conversation['metadata'])) {
                        $conversation['metadata'] =$this->decodeJson($conversation['metadata']);}
                    return $conversation;
               })->all();
        } catch (Throwable $e) {
            report($e);
            return [];
        }
    }
    public function touch(int $conversationId): bool {
        if (! $this->tableExists('ai_conversations')) {
            return false;
        }
        try {
            $data = ['updated_at' => now(),];
            $data = $this->filterColumns('ai_conversations',$data);
            return DB::table('ai_conversations')->where('id',$conversationId)->update($data) > 0;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }
    public function close(int $conversationId): bool {
        if (!$this->tableExists('ai_conversations')) {return false;}
        try {
            $columns = $this->columns('ai_conversations');
            $data = ['updated_at' => now(),];
            if (in_array('status',$columns,true)) {
                $data['status'] = 'closed';
            } elseif (in_array('is_active',$columns,true)) {
                $data['is_active'] = false;
            } else {
                return $this->touch($conversationId);
            }
            $data = $this->filterColumns('ai_conversations',$data);
            return DB::table('ai_conversations')->where('id',$conversationId)->update($data) > 0;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }
    public function historyForAi(int $conversationId,?int $limit = 30): array {
        $messages = $this->messages($conversationId,$limit);
        $history = [];
        foreach ($messages as $message) {
            $role = $message['role'] ?? null;
            $content =$message['content']?? $message['message']?? null;
            if (! is_string($role) || ! is_string($content) || trim($content) === '') {
                continue;
            }
            if (! in_array($role,['user','assistant','system',],true)) {
                continue;
            }
            $history[] = ['role' => $role,'content' => $content,];
        }
        return $history;
    }
    protected function tableExists(string $table): bool {
        try {
            return Schema::hasTable($table);
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }
    protected function columns(string $table): array {
        try {return Schema::getColumnListing($table);
        } catch (Throwable $e) {
            report($e);
            return [];
        }
    }
    protected function filterColumns(string $table,array $data): array {
        $columns = $this->columns($table);
        if (empty($columns)) {return [];}
        return array_intersect_key($data,array_flip($columns));
    }
    protected function encodeJson(array $data): ?string {
        if (empty($data)) {return null;}
        $json = json_encode($data,JSON_UNESCAPED_UNICODE |JSON_UNESCAPED_SLASHES);
        return $json === false? null: $json;
    }
    protected function decodeJson($value): array {
        if (is_array($value)) {return $value;}
        if (! is_string($value) ||trim($value) === '') {return [];}
        $decoded = json_decode($value,true);
        return is_array($decoded)? $decoded: [];
    }
}
