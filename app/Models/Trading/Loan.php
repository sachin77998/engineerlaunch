<?php

namespace App\Models\Trading;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A loan with its amortisation terms. */
class Loan extends Model
{
    protected $table = 'loans';
    protected $guarded = ['id'];

    protected $casts = ['principal' => 'float', 'interest_rate' => 'float', 'emi' => 'float', 'total_interest' => 'float', 'total_payment' => 'float', 'issue_date' => 'date', 'first_due_date' => 'date', 'maturity_date' => 'date'];

    public function getRouteKeyName(): string
    {
        return 'loan_no';
    }

    public function schedule(): HasMany
    {
        return $this->hasMany(LoanSchedule::class)->orderBy('installment_no');
    }
}
