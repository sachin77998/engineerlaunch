<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const STATUSES = ['pending', 'success', 'failed', 'refund_pending', 'refunded', 'partially_refunded'];
    protected $fillable = ['user_id', 'order_id', 'payable_type', 'payable_id', 'provider', 'amount_paise', 'currency', 'status', 'gateway_txn_id', 'bank_txn_id', 'payment_mode', 'gateway_response', 'verified_at'];
    protected $casts = ['gateway_response' => 'array', 'verified_at' => 'datetime', 'amount_paise' => 'integer'];

    public function premiumOrder()
    {
        return $this->belongsTo(PremiumOrder::class, 'payable_id');
    }
}
