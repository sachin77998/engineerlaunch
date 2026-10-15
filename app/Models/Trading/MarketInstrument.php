<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A stock or ETF listed on NSE or BSE, with its Kite token and end-of-day price. */
class MarketInstrument extends Model
{
    protected $table = 'market_instruments';
    protected $guarded = ['id'];

    protected $casts = ['last_price' => 'float', 'prev_close' => 'float', 'open' => 'float', 'high' => 'float', 'low' => 'float', 'change_pct' => 'float', 'turnover_lakhs' => 'float', 'margin_pct' => 'float', 'tick_size' => 'float', 'face_value' => 'float', 'price_date' => 'date', 'listed_on' => 'date', 'is_active' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StockOrder::class);
    }
}
