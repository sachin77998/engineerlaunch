<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IndustrialJob extends Model
{
    use \App\Models\Concerns\HasIndustrialSource;

    protected $fillable = [
        'industrial_area_id','industrial_company_id','department_id','job_role_id','external_id',
        'job_title','employment_type','experience_min','experience_max',
        'salary_min','salary_max','salary_period','qualification',
        'skills','description','source_url',
        'application_deadline',
        'is_verified','is_active','source_name',
        'last_verified_at','verification_status',
    ];

    protected $casts = ['industrial_area_id' => 'integer','industrial_company_id' => 'integer','department_id' => 'integer',
        'job_role_id' => 'integer','experience_min' => 'decimal:1','experience_max' => 'decimal:1',
        'salary_min' => 'decimal:2','salary_max' => 'decimal:2','last_verified_at' => 'datetime',
        'application_deadline' => 'datetime','skills' => 'array','is_verified' => 'boolean','is_active' => 'boolean',
    ];
    public function area()
    {
        return $this->belongsTo(IndustrialArea::class,'industrial_area_id');
    }
    public function company()
    {
        return $this->belongsTo(IndustrialCompany::class,'industrial_company_id');
    }
    public function department()
    {
        return $this->belongsTo(IndustrialDepartment::class,'department_id');
    }
    public function role()
    {
        return $this->belongsTo(IndustrialJobRole::class,'job_role_id');
    }
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('industrial_jobs.is_active',true)->where(fn(Builder $q) =>$q->whereNull('industrial_jobs.application_deadline')->orWhere('industrial_jobs.application_deadline','>',now()))
            ->whereHas('area',fn(Builder $q) =>$q->visible())
            ->where(fn(Builder $q) =>$q->whereNull('industrial_jobs.industrial_company_id')
                    ->orWhereHas('company',fn(Builder $company) =>$company
                            ->visible()
                            ->whereColumn('industrial_companies.industrial_area_id','industrial_jobs.industrial_area_id')))
            ->where(fn(Builder $q) =>$q->whereNull('industrial_jobs.department_id')
                    ->orWhereHas('department',fn(Builder $department) =>$department->where('is_active',true)))
            ->where(fn(Builder $q) =>$q
                    ->whereNull('industrial_jobs.job_role_id')
                    ->orWhereHas('role',fn(Builder $role) =>$role
                    ->where('is_active',true)
                    ->whereColumn('industrial_job_roles.department_id','industrial_jobs.department_id')
                    ->whereHas('department',fn(Builder $department) =>$department->where('is_active',true))));
    }
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('industrial_jobs.is_active',true);
    }
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('industrial_jobs.is_verified',true);
    }
    public function scopeInArea(Builder $query,int $areaId): Builder {
        return $query->where('industrial_jobs.industrial_area_id',$areaId);
    }
    public function scopeForCompany(Builder $query,int $companyId): Builder {
        return $query->where('industrial_jobs.industrial_company_id',$companyId);
    }
    public function scopeForDepartment(Builder $query,int $departmentId): Builder {
        return $query->where('industrial_jobs.department_id',$departmentId);
    }
    public function scopeForRole(Builder $query,int $roleId): Builder {
        return $query->where('industrial_jobs.job_role_id',$roleId);
    }
    public function scopeEmploymentType(Builder $query,string $employmentType): Builder {
        return $query->where('industrial_jobs.employment_type',$employmentType);
    }
    public function scopeQualification(Builder $query,string $qualification): Builder {
        return $query->where('industrial_jobs.qualification','like','%' . trim($qualification) . '%');
    }
    public function scopeWithSkill(Builder $query,string $skill): Builder {
        return $query->whereJsonContains('industrial_jobs.skills',trim($skill));
    }
    public function getTitleAttribute(): string
    {
        return (string) $this->job_title;
    }
    public function isExpired(): bool
    {
        return $this->application_deadline !== null && $this->application_deadline->isPast();
    }
    public function isLive(): bool
    {
        if (! $this->is_active) {return false;}
        if ($this->isExpired()) {return false;}
        if ($this->relationLoaded('area') && $this->area && ! $this->area->is_active) {
            return false;
        }

        return true;
    }
}
