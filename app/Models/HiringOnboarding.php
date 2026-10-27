<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// One new hire's joining checklist; the candidate works through it on a private link (token stored hashed).
class HiringOnboarding extends Model
{
    public const STATUSES = ['in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
    protected $fillable = ['owner_id', 'job_application_id', 'started_by', 'status', 'token_hash', 'token_expires_at', 'due_on', 'completed_at'];
    protected $casts = ['token_expires_at' => 'datetime', 'due_on' => 'date', 'completed_at' => 'datetime'];

    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    public function items()
    {
        return $this->hasMany(HiringOnboardingItem::class, 'onboarding_id')->orderBy('position');
    }

    /** Share of required items finished (approved documents, completed tasks), 0-100. */
    public function getProgressAttribute(): int
    {
        $required = $this->items->where('required', true);
        if ($required->isEmpty()) return 100;
        return (int) round($required->filter(fn ($i) => $i->isDone())->count() / $required->count() * 100);
    }
}
