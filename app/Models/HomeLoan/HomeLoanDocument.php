<?php

namespace App\Models\HomeLoan;

use Illuminate\Database\Eloquent\Model;

// Stored on the private "local" disk; only the applicant and the site owner can download it.
class HomeLoanDocument extends Model
{
    protected $guarded = [];

    public function application() { return $this->belongsTo(HomeLoanApplication::class, 'home_loan_application_id'); }

    public function label(): string
    {
        return config('home_loans.documents')[$this->kind][0] ?? $this->kind;
    }
}
