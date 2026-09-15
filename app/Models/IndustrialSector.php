<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrialSector extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function subsectors()
    {
        return $this->hasMany(IndustrialSubsector::class, 'sector_id');
    }

    public function assets()
    {
        return $this->hasMany(IndustrialAsset::class, 'sector_id')->where('is_active', true)->orderBy('sort_order');
    }
 public function companies(){return $this->belongsToMany(IndustrialCompany::class,'industrial_company_sectors','sector_id','industrial_company_id');}
}
