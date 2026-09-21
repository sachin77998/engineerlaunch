<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrialCompanySource extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['checked_at' => 'datetime'];
    public function company()
    {
        return $this->belongsTo(IndustrialCompany::class, 'industrial_company_id');
    }
}
