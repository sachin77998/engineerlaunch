<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    use HasFactory;
    protected $table = 'ai_conversations';
    protected $fillable = ['user_id','title','agent','status','context','metadata',];

    protected $casts = ['context' => 'array','metadata' => 'array',];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }
    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class,'conversation_id')->orderBy('created_at');
    }
    public function scopeActive($query)
    {
        return $query->where('status','active');
    }
    public function scopeForAgent($query,string $agent) 
    {
        return $query->where('agent',$agent);
    }
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
    public function close(): bool
    {
        $this->status = 'closed';
        return $this->save();
    }
    public function reopen(): bool
    {
        $this->status = 'active';
        return $this->save();
    }
}
