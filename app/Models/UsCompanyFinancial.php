<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsCompanyFinancial extends Model
{
    protected $guarded = [];
    protected $casts = ['period_end' => 'date', 'balance_sheet_date' => 'date'];
}
