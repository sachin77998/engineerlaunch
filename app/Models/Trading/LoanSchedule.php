<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;

/** One instalment of a loan's amortisation schedule. */
class LoanSchedule extends Model
{
    protected $table = 'loan_schedules';
    protected $guarded = ['id'];

    public $timestamps = false;
    protected $casts = ['due_date' => 'date', 'opening_balance' => 'float', 'scheduled_payment' => 'float', 'principal' => 'float', 'interest' => 'float', 'total_payment' => 'float', 'closing_balance' => 'float', 'daily_interest' => 'float', 'is_moratorium' => 'boolean'];
}
