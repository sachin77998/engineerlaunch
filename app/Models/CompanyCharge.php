<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Company insights: registered charges (secured borrowings). */
class CompanyCharge extends Model
{
    protected $table = 'company_charges';
    protected $guarded = ['id'];
    protected $casts = ['created_on' => 'date', 'modified_on' => 'date', 'satisfied_on' => 'date'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
