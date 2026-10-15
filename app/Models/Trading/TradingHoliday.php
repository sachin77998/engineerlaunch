<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** Exchange holiday. */
class TradingHoliday extends Model
{
    protected $table = 'trading_holidays';
    protected $guarded = ['id'];
    protected $casts = ['holiday_on' => 'date'];
}
