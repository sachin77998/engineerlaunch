<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** Nominee of a trading client. */
class TradingClientNominee extends Model
{
    protected $table = 'trading_client_nominees';
    protected $guarded = ['id'];
    protected $casts = ['id_number' => 'encrypted', 'dob' => 'date', 'share_percent' => 'float', 'is_minor' => 'boolean'];
    protected $hidden = ['id_number'];
}
