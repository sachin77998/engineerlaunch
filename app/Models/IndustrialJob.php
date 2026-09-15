<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IndustrialJob extends Model
{
    use \App\Models\Concerns\HasIndustrialSource;

    protected $fillable = [
        'industrial_area_id',
        'industrial_company_id',
        'department_id',
        'job_role_id',
        'external_id',
        'job_title',
        'employment_type',
        'experience_min',
        'experience_max',
        'salary_min',
        'salary_max',
        'salary_period',
        'qualification',
        'skills',
        'description',
        'source_url',
        'application_deadline',
        'is_verified',
        'is_active',
        'source_name',
        'last_verified_at',
        'verification_status',
    ];

    protected $casts = [
        'industrial_area_id' => 'integer',
        'industrial_company_id' => 'integer',
        'department_id' => 'integer',
        'job_role_id' => 'integer',

        'experience_min' => 'decimal:1',
        'experience_max' => 'decimal:1',

        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',

        'last_verified_at' => 'datetime',
        'application_deadline' => 'datetime',

        'skills' => 'array',

        'is_verified' => 'boolean',
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Industrial Area / Cluster / Estate.
     */
    public function area()
    {
        return $this->belongsTo(
            IndustrialArea::class,
            'industrial_area_id'
        );
    }

    /**
     * Company / Plant / Factory.
     */
    public function company()
    {
        return $this->belongsTo(
            IndustrialCompany::class,
            'industrial_company_id'
        );
    }

    /**
     * Department.
     */
    public function department()
    {
        return $this->belongsTo(
            IndustrialDepartment::class,
            'department_id'
        );
    }

    /**
     * Job Role.
     */
    public function role()
    {
        return $this->belongsTo(
            IndustrialJobRole::class,
            'job_role_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Live industrial job openings.
     *
     * A job is considered live when:
     *
     * 1. It is active.
     * 2. Its application deadline has not passed.
     * 3. Its industrial area is active.
     * 4. Its company is active when a company is attached.
     * 5. Its department is active when a department is attached.
     * 6. Its role is active and belongs to the selected department when
     *    a role is attached.
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query
            ->where(
                'industrial_jobs.is_active',
                true
            )

            /*
            |--------------------------------------------------------------------------
            | Application deadline
            |--------------------------------------------------------------------------
            */
            ->where(
                fn(Builder $q) =>
                $q
                    ->whereNull(
                        'industrial_jobs.application_deadline'
                    )
                    ->orWhere(
                        'industrial_jobs.application_deadline',
                        '>',
                        now()
                    )
            )

            /*
            |--------------------------------------------------------------------------
            | Industrial Area
            |--------------------------------------------------------------------------
            */
            ->whereHas(
                'area',
                fn(Builder $q) =>
                $q->visible()
            )

            /*
            |--------------------------------------------------------------------------
            | Company / Plant
            |--------------------------------------------------------------------------
            |
            | Company can be nullable for jobs which are not yet mapped to a
            | particular company.
            |
            */
            ->where(
                fn(Builder $q) =>
                $q
                    ->whereNull(
                        'industrial_jobs.industrial_company_id'
                    )
                    ->orWhereHas(
                        'company',
                        fn(Builder $company) =>
                        $company
                            ->visible()
                            ->whereColumn(
                                'industrial_companies.industrial_area_id',
                                'industrial_jobs.industrial_area_id'
                            )
                    )
            )

            /*
            |--------------------------------------------------------------------------
            | Department
            |--------------------------------------------------------------------------
            */
            ->where(
                fn(Builder $q) =>
                $q
                    ->whereNull(
                        'industrial_jobs.department_id'
                    )
                    ->orWhereHas(
                        'department',
                        fn(Builder $department) =>
                        $department->where(
                            'is_active',
                            true
                        )
                    )
            )

            /*
            |--------------------------------------------------------------------------
            | Job Role
            |--------------------------------------------------------------------------
            */
            ->where(
                fn(Builder $q) =>
                $q
                    ->whereNull(
                        'industrial_jobs.job_role_id'
                    )
                    ->orWhereHas(
                        'role',
                        fn(Builder $role) =>
                        $role
                            ->where(
                                'is_active',
                                true
                            )
                            ->whereColumn(
                                'industrial_job_roles.department_id',
                                'industrial_jobs.department_id'
                            )
                            ->whereHas(
                                'department',
                                fn(Builder $department) =>
                                $department->where(
                                    'is_active',
                                    true
                                )
                            )
                    )
            );
    }

    /**
     * Only active jobs.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            'industrial_jobs.is_active',
            true
        );
    }

    /**
     * Only verified jobs.
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where(
            'industrial_jobs.is_verified',
            true
        );
    }

    /**
     * Jobs belonging to an industrial area.
     */
    public function scopeInArea(
        Builder $query,
        int $areaId
    ): Builder {
        return $query->where(
            'industrial_jobs.industrial_area_id',
            $areaId
        );
    }

    /**
     * Jobs belonging to a company / plant.
     */
    public function scopeForCompany(
        Builder $query,
        int $companyId
    ): Builder {
        return $query->where(
            'industrial_jobs.industrial_company_id',
            $companyId
        );
    }

    /**
     * Jobs belonging to a department.
     */
    public function scopeForDepartment(
        Builder $query,
        int $departmentId
    ): Builder {
        return $query->where(
            'industrial_jobs.department_id',
            $departmentId
        );
    }

    /**
     * Jobs belonging to a job role.
     */
    public function scopeForRole(
        Builder $query,
        int $roleId
    ): Builder {
        return $query->where(
            'industrial_jobs.job_role_id',
            $roleId
        );
    }

    /**
     * Jobs with a specific employment type.
     */
    public function scopeEmploymentType(
        Builder $query,
        string $employmentType
    ): Builder {
        return $query->where(
            'industrial_jobs.employment_type',
            $employmentType
        );
    }

    /**
     * Jobs matching a qualification.
     */
    public function scopeQualification(
        Builder $query,
        string $qualification
    ): Builder {
        return $query->where(
            'industrial_jobs.qualification',
            'like',
            '%' . trim($qualification) . '%'
        );
    }

    /**
     * Jobs requiring a particular skill.
     */
    public function scopeWithSkill(
        Builder $query,
        string $skill
    ): Builder {
        return $query->whereJsonContains(
            'industrial_jobs.skills',
            trim($skill)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Human-readable job title.
     */
    public function getTitleAttribute(): string
    {
        return (string) $this->job_title;
    }

    /**
     * Check whether the application deadline has passed.
     */
    public function isExpired(): bool
    {
        return $this->application_deadline !== null
            && $this->application_deadline->isPast();
    }

    /**
     * Check whether this opening is currently live.
     *
     * This is useful when displaying an individual job.
     */
    public function isLive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if (
            $this->relationLoaded('area')
            && $this->area
            && ! $this->area->is_active
        ) {
            return false;
        }

        return true;
    }
}
