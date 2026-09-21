<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrialCompany extends Model
{
    use \App\Models\Concerns\HasIndustrialSource;

    protected $fillable = [
        'industrial_area_id',
        'name',
        'slug',
        'industry',
        'sector',
        'plant_name',
        'website',
        'careers_url',
        'description',
        'is_verified',
        'is_active',
        'source_name',
        'source_url',
        'last_verified_at',
        'verification_status',
        'facility_type',
    ];

    protected $casts = [
        'industrial_area_id' => 'integer',
        'last_verified_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function area()
    {
        return $this->belongsTo(IndustrialArea::class, 'industrial_area_id');
    }

    public function jobs()
    {
        return $this->hasMany(IndustrialJob::class);
    }
    public function scopeVisible($query)
    {
        return $query->where('is_active', true)->where('is_verified', true)->whereIn('verification_status', ['government_source','company_source','admin_verified',])->whereNotNull('last_verified_at')->whereNotNull('source_url')->whereHas('area', fn($q) => $q->visible());
    }
    public function sectors()
    {
        return $this->belongsToMany(IndustrialSector::class,'industrial_company_sectors','industrial_company_id','sector_id');
    }
    public function subsectors()
    {
        return $this->belongsToMany(IndustrialSubsector::class,'industrial_company_subsectors','industrial_company_id','subsector_id');
    }
    public function processes()
    {
        return $this->belongsToMany(IndustrialProcess::class,'industrial_company_processes','industrial_company_id','process_id');
    }
    public function sources()
    {
        return $this->hasMany(IndustrialCompanySource::class,'industrial_company_id');
    }
}
