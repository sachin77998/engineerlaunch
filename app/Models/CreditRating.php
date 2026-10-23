<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Company insights: one rating action read from a rating-agency rationale (ICRA or CARE). */
class CreditRating extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'published_on' => 'date', 'withdrawn' => 'boolean', 'non_cooperating' => 'boolean', 'total_rated_crore' => 'float',
        'instruments' => 'array', 'debt_instruments' => 'array', 'financials' => 'array', 'strengths' => 'array', 'challenges' => 'array', 'sensitivities' => 'array', 'funding_events' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
