<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** One status change of an order. */
class MfOrderEvent extends Model
{
    protected $table = 'mf_order_events';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];
}
