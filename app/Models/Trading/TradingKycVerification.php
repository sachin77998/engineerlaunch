<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** One KYC check and its (masked) result. */
class TradingKycVerification extends Model
{
    protected $table = 'trading_kyc_verifications';
    protected $guarded = ['id'];
    protected $casts = ['response' => 'array', 'score' => 'float'];
}
