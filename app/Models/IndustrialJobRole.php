<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrialJobRole extends Model
{
    use \App\Models\Concerns\HasIndustrialSource;
    protected $fillable = ['department_id', 'name', 'slug', 'is_active', 'source_name', 'source_url', 'last_verified_at', 'verification_status'];
    protected $casts = ['department_id' => 'integer', 'last_verified_at' => 'datetime', 'is_active' => 'boolean'];
    public function department()
    {
        return $this->belongsTo(IndustrialDepartment::class, 'department_id');
    }
    public function jobs()
    {
        return $this->hasMany(IndustrialJob::class, 'job_role_id');
    }
    public function aliases()
    {
        return $this->hasMany(IndustrialJobRoleAlias::class, 'job_role_id');
    }
    public function processes()
    {
        return $this->belongsToMany(IndustrialProcess::class, 'industrial_process_job_roles', 'job_role_id', 'process_id');
    }
    public function profile()
    {
        return $this->hasOne(IndustrialRoleProfile::class, 'job_role_id');
    }
    public function sectors()
    {
        return $this->belongsToMany(IndustrialSector::class, 'industrial_role_sectors', 'job_role_id', 'sector_id');
    }
    public function relatedRoles()
    {
        return $this->belongsToMany(self::class, 'industrial_related_roles', 'job_role_id', 'related_role_id')->withPivot('reason', 'weight');
    }
}
