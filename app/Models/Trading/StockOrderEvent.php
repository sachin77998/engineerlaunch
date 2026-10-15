<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** One status update of a stock order. */
class StockOrderEvent extends Model
{
    protected $table = 'stock_order_events';
    protected $guarded = ['id'];

    public $timestamps = false;
    protected $casts = ['payload' => 'array', 'created_at' => 'datetime'];
}
