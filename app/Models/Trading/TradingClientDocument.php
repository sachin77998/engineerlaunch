<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** KYC document stored on the private disk. */
class TradingClientDocument extends Model
{
    protected $table = 'trading_client_documents';
    protected $guarded = ['id'];
    protected $casts = ['reviewed_at' => 'datetime'];
    protected $hidden = ['path'];
}
