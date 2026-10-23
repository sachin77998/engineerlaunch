<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Company insights: company registry details. */
class CompanyRegistryDetail extends Model
{
    protected $table = 'company_registry_details';
    protected $guarded = ['id'];
    protected $casts = ['gstins' => 'array', 'incorporation_date' => 'date', 'last_agm_date' => 'date', 'last_balance_sheet_date' => 'date', 'enriched_at' => 'datetime'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
