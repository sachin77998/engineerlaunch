<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** A saved calculator result. */
class CalculatorRun extends Model
{
    protected $table = 'calculator_runs';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = ['inputs' => 'array', 'result' => 'array', 'created_at' => 'datetime'];
}
