<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortalEventNotification extends Model
{
    protected $fillable = ['portal_event_id', 'user_id', 'email', 'status', 'error_message', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(PortalEvent::class, 'portal_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
