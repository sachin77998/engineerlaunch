<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Mutual fund order; its status history is in mf_order_events. */
class MfOrder extends Model
{
    protected $table = 'mf_orders';
    protected $guarded = ['id'];
    protected $casts = ['amount' => 'float', 'units' => 'float', 'nav' => 'float', 'allotted_units' => 'float', 'stamp_duty' => 'float', 'transaction_charge' => 'float', 'platform_fee' => 'float', 'gst' => 'float', 'total_fees' => 'float', 'net_amount' => 'float', 'all_units' => 'boolean', 'applicable_nav_date' => 'date', 'otp_expires_at' => 'datetime', 'verified_at' => 'datetime', 'submitted_at' => 'datetime', 'allotted_at' => 'datetime'];
    protected $hidden = ['otp_hash', 'otp_attempts', 'idempotency_key', 'ip', 'user_agent'];

    public function getRouteKeyName(): string
    {
        return 'order_no';
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(TradingClient::class, 'trading_client_id');
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(MfScheme::class, 'scheme_code', 'scheme_code');
    }

    public function events(): HasMany
    {
        return $this->hasMany(MfOrderEvent::class)->orderBy('id');
    }
}
