<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** Minor-to-major change of a client's account. */
class TradingMinorMajor extends Model
{
    protected $table = 'trading_minor_major';
    protected $guarded = ['id'];
    protected $casts = ['new_pan' => 'encrypted', 'new_bank_account' => 'encrypted', 'major_on' => 'date', 'completed_at' => 'datetime'];
    protected $hidden = ['new_pan', 'new_bank_account'];
}
