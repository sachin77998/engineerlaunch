<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortalEvent extends Model
{
    protected $fillable = ['title', 'slug', 'description', 'event_type', 'image_url', 'cta_text', 'cta_url', 'is_active', 'email_enabled', 'scheduled_at', 'sent_at', 'target_users', 'emails_sent', 'emails_failed'];

    protected $casts = ['is_active' => 'boolean', 'email_enabled' => 'boolean', 'scheduled_at' => 'datetime', 'sent_at' => 'datetime'];

    public function notifications(): HasMany
    {
        return $this->hasMany(PortalEventNotification::class);
    }
}
