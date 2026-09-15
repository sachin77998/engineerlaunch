<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrialSubsector extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function sector()
    {
        return $this->belongsTo(IndustrialSector::class, 'sector_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function processes()
    {
        return $this->belongsToMany(IndustrialProcess::class, 'industrial_subsector_processes', 'subsector_id', 'process_id')->withPivot('sort_order')->orderByPivot('sort_order');
    }
}
