<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Company insights: a company's record at a rating agency (agency id, current ratings, lenders, last check). */
class CreditRatingSource extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['current' => 'array', 'lenders' => 'array', 'checked_at' => 'datetime'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
