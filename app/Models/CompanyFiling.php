<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Company insights: company filings. */
class CompanyFiling extends Model
{
    protected $table = 'company_filings';
    protected $guarded = ['id'];
    protected $casts = ['parsed_fields' => 'array'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
