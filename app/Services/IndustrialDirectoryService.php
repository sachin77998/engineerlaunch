<?php

namespace App\Services;

use App\Models\IndustrialArea;
use App\Models\IndustrialCompany;
use App\Models\IndustrialDepartment;
use App\Models\IndustrialJob;
use App\Models\IndustrialJobRole;
use App\Models\IndustrialState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class IndustrialDirectoryService
{
    public function states(): Collection
    {
        return IndustrialState::query()->where('is_active', true)->orderBy('name')->get(['id','name','slug',]);
    }
    public function locations(int $stateId): Collection
    {
        $areas = IndustrialArea::query()->visible()->where('state_id', $stateId);
        return IndustrialArea::locationNames($areas);
    }
    public function areas(int $stateId,?string $location = null): Collection {
        return IndustrialArea::query()->visible()->where('state_id', $stateId)->when($location,fn(Builder $query) => $query->atLocation($location))->with('state')->withCount(['companies as companies_count' => fn($query) =>$query->visible(),'jobs as live_jobs_count' => fn($query) =>$query->live(),])->orderByDesc('is_featured')->orderBy('name')->get(['id','state_id','name','slug','city','district','area_type','sectors','description','latitude','longitude','is_featured',]);
    }
    public function area(int $areaId): ?IndustrialArea
    {
        return IndustrialArea::query()->visible()->with('state')->withCount(['companies as companies_count' => fn($query) =>$query->visible(),'jobs as live_jobs_count' => fn($query) =>$query->live(),])->find($areaId);
    }
    public function companies(int $areaId): Collection
    {
        return IndustrialCompany::query()->visible()->where('industrial_area_id', $areaId)->with('area.state')->withCount(['jobs as live_jobs_count' => fn($query) =>$query->live(),])->orderBy('name')->get();
    }
    public function company(int $companyId): ?IndustrialCompany
    {
        return IndustrialCompany::query()->visible()->with('area.state')->withCount(['jobs as live_jobs_count' => fn($query) =>$query->live(),])->find($companyId);
    }
    public function departments(?int $companyId = null,?int $areaId = null): Collection {
        $query = IndustrialDepartment::query()->where('is_active', true);
        if ($companyId) {
            $query->whereIn('id',IndustrialJob::query()->live()->where('industrial_company_id', $companyId)->whereNotNull('department_id')->select('department_id'));
        } elseif ($areaId) {
            $query->whereIn('id',IndustrialJob::query()->live()->where('industrial_area_id', $areaId)->whereNotNull('department_id')->select('department_id'));
        }
        return $query->orderBy('name')->get(['id','name','slug',]);
    }
    public function roles(int $departmentId,?int $companyId = null,?int $areaId = null): Collection {
        $query = IndustrialJobRole::query()->where('is_active', true)->where('department_id', $departmentId);
        if ($companyId) {
            $query->whereIn('id',IndustrialJob::query()->live()->where('industrial_company_id', $companyId)->whereNotNull('job_role_id')->select('job_role_id'));
        } elseif ($areaId) {
            $query->whereIn('id',IndustrialJob::query()->live()->where('industrial_area_id', $areaId)->whereNotNull('job_role_id')->select('job_role_id'));
        }
        return $query->orderBy('name')->get(['id','name','slug',]);
    }
    public function liveJobs(array $filters = []): Builder
    {
        $query = IndustrialJob::query()->live()->with(['area.state','company','department','role',]);
        
        if (!empty($filters['state_id'])) {
            $query->whereHas('area',fn(Builder $area) =>$area->where('state_id', $filters['state_id']));
        }
        if (!empty($filters['location'])) {
            $location = trim($filters['location']);
            $query->whereHas('area',fn(Builder $area) =>$area->atLocation($location));
        }
        if (!empty($filters['area_id'])) {
            $query->where('industrial_area_id',$filters['area_id']);
        }
        if (!empty($filters['company_id'])) {
            $query->where('industrial_company_id',$filters['company_id']);
        }
        if (!empty($filters['department_id'])) {
            $query->where('department_id',$filters['department_id']);
        }
        if (!empty($filters['job_role_id'])) {$query->where('job_role_id',$filters['job_role_id']);
        }
        if (!empty($filters['process_id']) && $this->jobHasColumn('process_id')) {
            $query->where('process_id',$filters['process_id']);
        }
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $job) use ($search) {
                $job->where('job_title','like','%' . $search . '%')->orWhere('description','like','%' . $search . '%')
                    ->orWhere('qualification','like','%' . $search . '%')
                    ->orWhereHas('company',fn(Builder $company) =>$company->where('name','like','%' . $search . '%'))
                    ->orWhereHas('role',fn(Builder $role) =>$role->where('name','like','%' . $search . '%'));
            });
        }
        if (isset($filters['experience_min']) && is_numeric($filters['experience_min'])) {
            $minimum = (float) $filters['experience_min'];
            $query->where(function (Builder $job) use ($minimum) {
                $job->whereNull('experience_max')->orWhere('experience_max','>=',$minimum);
            });
        }
        if (!empty($filters['qualification'])) {
            $query->where('qualification','like','%' . $filters['qualification'] . '%');
        }
        if (!empty($filters['employment_type'])) {
            $query->where('employment_type',$filters['employment_type']);
        }
        if (!empty($filters['skill'])) {
            $skill = trim($filters['skill']);
            if ($query->getModel()->getConnection()->getDriverName() === 'sqlite') {
                $query->whereRaw('EXISTS (SELECT 1 FROM json_each(industrial_jobs.skills) WHERE json_each.value = ?)',[$skill]);
            } else {
                $query->whereJsonContains('skills',$skill);
            }
        }
        if (isset($filters['salary_min']) && is_numeric($filters['salary_min'])) {
            $salaryMinimum = (float) $filters['salary_min'];
            $query->whereNotNull('salary_min')->whereRaw("COALESCE(salary_max, salary_min) /CASE WHEN salary_period = 'annual' THEN 12 ELSE 1 END >= ?",[$salaryMinimum]);
        }
        return $query->latest('id');
    }
    public function paginateLiveJobs(array $filters = [],int $perPage = 12) {
        $paginator = $this->liveJobs($filters)->paginate($perPage,['*'],'jobs_page');
        return $paginator->appends(request()->query());
    }
    public function areaSummary(int $areaId): array
    {
        $area = $this->area($areaId);
        if (!$area) {
            return ['area' => null,'companies_count' => 0,'live_jobs_count' => 0,'departments_count' => 0,'roles_count' => 0,];
        }
        $companies = $this->companies($areaId);
        $liveJobsQuery = IndustrialJob::query()->live()->where('industrial_area_id',$areaId);
        $departmentsCount = (clone $liveJobsQuery)->whereNotNull('department_id')->distinct('department_id')->count('department_id');
        $rolesCount = (clone $liveJobsQuery)->whereNotNull('job_role_id')->distinct('job_role_id')->count('job_role_id');
        return ['area' => $area,'companies_count' => $companies->count(),'live_jobs_count' => $liveJobsQuery->count(),'departments_count' => $departmentsCount,'roles_count' => $rolesCount,];
    }
    public function companySummary(int $companyId): array
    {
        $company = $this->company($companyId);
        if (!$company) {
            return ['company' => null,'live_jobs_count' => 0,'departments_count' => 0,'roles_count' => 0,];
        }
        $jobs = IndustrialJob::query()->live()->where('industrial_company_id',$companyId);
        return ['company' => $company,'live_jobs_count' => (clone $jobs)->count(),'departments_count' => (clone $jobs)->whereNotNull('department_id')->distinct('department_id')->count('department_id'),'roles_count' => (clone $jobs)->whereNotNull('job_role_id')->distinct('job_role_id')->count('job_role_id'),
        ];
    }
    public function hierarchy(int $areaId): array
    {
        $area = $this->area($areaId);
        if (!$area) {return ['area' => null,'companies' => collect(),'departments' => collect(),'jobs' => collect(),];}
        $companies = $this->companies($areaId);
        $departments = $this->departments(areaId: $areaId);
        $jobs = $this->liveJobs(['area_id' => $areaId,])->get();
        return ['area' => $area,'companies' => $companies,'departments' => $departments,'jobs' => $jobs,];
    }
    private function jobHasColumn(string $column): bool
    {
        return Schema::hasColumn('industrial_jobs',$column);
    }
}
