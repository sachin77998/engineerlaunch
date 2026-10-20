<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PremiumOrder extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['paid_at' => 'datetime', 'activated_at' => 'datetime', 'premium_until' => 'datetime',
        'base_amount' => 'float', 'gst_rate' => 'float', 'gst_amount' => 'float', 'total_amount' => 'float'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
