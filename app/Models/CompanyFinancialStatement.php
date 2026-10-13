<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Company insights: company financial statements. */
class CompanyFinancialStatement extends Model
{
    protected $table = 'company_financial_statements';
    protected $guarded = ['id'];
    protected $casts = ['period_end' => 'date', 'is_consolidated' => 'boolean'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
