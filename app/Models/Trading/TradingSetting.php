<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** Editable trading setting (fees, cut-off times, minimum amounts). */
class TradingSetting extends Model
{
    protected $table = 'trading_settings';
    protected $guarded = ['id'];
}
