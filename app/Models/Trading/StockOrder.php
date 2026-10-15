<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A stock/ETF order sent to the investor's broker. */
class StockOrder extends Model
{
    protected $table = 'stock_orders';
    protected $guarded = ['id'];

    protected $casts = ['price' => 'float', 'trigger_price' => 'float', 'average_price' => 'float', 'charges' => 'array'];
    protected $hidden = ['idempotency_key', 'ip'];

    public function getRouteKeyName(): string
    {
        return 'order_no';
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(MarketInstrument::class, 'market_instrument_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(StockOrderEvent::class)->orderBy('id');
    }
}
