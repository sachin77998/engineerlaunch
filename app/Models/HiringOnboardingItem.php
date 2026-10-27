<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// A document the new hire uploads (pending → submitted → approved / rejected) or a task the team completes (pending → done).
class HiringOnboardingItem extends Model
{
    public const STATUSES = ['pending' => 'Pending', 'submitted' => 'To review', 'approved' => 'Approved', 'rejected' => 'Re-upload needed', 'done' => 'Done'];
    protected $fillable = ['onboarding_id', 'position', 'kind', 'label', 'help', 'required', 'status', 'file_path', 'file_name', 'file_size', 'submitted_at', 'reviewed_by', 'reviewed_at', 'review_note'];
    protected $casts = ['required' => 'boolean', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];

    public function onboarding()
    {
        return $this->belongsTo(HiringOnboarding::class, 'onboarding_id');
    }

    public function isDone(): bool
    {
        return in_array($this->status, ['approved', 'done'], true);
    }
}
