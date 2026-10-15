<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** Second or third holder of a joint account. */
class TradingJointHolder extends Model
{
    protected $table = 'trading_joint_holders';
    protected $guarded = ['id'];
    protected $casts = ['pan' => 'encrypted', 'dob' => 'date', 'pan_verified_at' => 'datetime'];
    protected $hidden = ['pan', 'pan_hash'];
}
