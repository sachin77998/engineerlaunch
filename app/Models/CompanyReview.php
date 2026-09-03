<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'user_id', 'job_id', 'rating', 'title', 'review',
        'pros', 'cons', 'relationship', 'status', 'is_verified_application',
        'moderated_by', 'moderated_at', 'moderation_note',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_application' => 'boolean',
        'moderated_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function job(): BelongsTo { return $this->belongsTo(Job::class); }
    public function moderator(): BelongsTo { return $this->belongsTo(User::class, 'moderated_by'); }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
