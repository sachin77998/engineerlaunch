<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialPrice extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['recorded_on' => 'date', 'price' => 'float', 'mrp' => 'float'];
}
