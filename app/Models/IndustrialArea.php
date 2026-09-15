<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrialArea extends Model
{
    use \App\Models\Concerns\HasIndustrialSource;

    protected $fillable = ['state_id', 'name', 'slug', 'district', 'city', 'pincode', 'area_type', 'sectors', 'description', 'latitude', 'longitude', 'source_name', 'source_url', 'is_featured', 'is_active', 'last_verified_at', 'verification_status'];

    protected $casts = ['state_id' => 'integer', 'last_verified_at' => 'datetime', 'sectors' => 'array', 'is_featured' => 'boolean', 'is_active' => 'boolean'];

    public function state()
    {
        return $this->belongsTo(IndustrialState::class, 'state_id');
    }

    public function companies()
    {
        return $this->hasMany(IndustrialCompany::class);
    }

    public function jobs()
    {
        return $this->hasMany(IndustrialJob::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_active', true)->whereHas('state', fn ($q) => $q->where('is_active', true));
    }

    public function scopeAtLocation($query, string $location)
    {
        return $query->where(fn ($q) => $q->where('city', $location)->orWhere('district', $location));
    }

    public static function locationNames($query)
    {
        return (clone $query)->whereNotNull('city')->distinct()->pluck('city')
            ->merge((clone $query)->whereNotNull('district')->distinct()->pluck('district'))
            ->filter(fn ($name) => trim($name) !== '')->unique()->sort()->values();
    }

public function sectorCatalog()
{
    return $this->belongsToMany(IndustrialSector::class, 'industrial_area_sectors', 'industrial_area_id', 'sector_id');
}
}
