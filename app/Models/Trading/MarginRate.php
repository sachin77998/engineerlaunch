<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** NSE applicable margin rate for a security. */
class MarginRate extends Model
{
    protected $table = 'margin_rates';
    protected $guarded = ['id'];
    protected $casts = ['var_margin' => 'float', 'elm' => 'float', 'applicable_margin' => 'float', 'as_of' => 'date'];
}
