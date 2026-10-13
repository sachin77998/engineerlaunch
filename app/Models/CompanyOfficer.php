<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Company insights: company officers. */
class CompanyOfficer extends Model
{
    protected $table = 'company_officers';
    protected $guarded = ['id'];
    protected $casts = ['appointed_on' => 'date', 'ceased_on' => 'date'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
