<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    use HasFactory;
    protected $table = 'ai_messages';
    protected $fillable = ['conversation_id','user_id','role','message','agent','payload','sources','metadata',];
    protected $casts = ['payload' => 'array','sources' => 'array','metadata' => 'array',];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class,'conversation_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function isUser(): bool
    {
        return $this->role === 'user';
    }
    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }
    public function isSystem(): bool
    {
        return $this->role === 'system';
    }
    public function isTool(): bool
    {
        return $this->role === 'tool';
    }
}
