<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrialProcess extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function roles()
    {
        return $this->belongsToMany(IndustrialJobRole::class, 'industrial_process_job_roles', 'process_id', 'job_role_id');
    }

    public function subsectors()
    {
        return $this->belongsToMany(IndustrialSubsector::class, 'industrial_subsector_processes', 'process_id', 'subsector_id');
    }
}
