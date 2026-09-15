<?php

namespace App\Services;

use App\Models\IndustrialArea;
use App\Models\IndustrialJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class IndustrialIntelligence
{
    /**
     * Generate intelligence for an industrial area.
     *
     * Hierarchy:
     *
     * State
     *   ↓
     * City / District
     *   ↓
     * Industrial Area
     *   ↓
     * Company / Plant
     *   ↓
     * Department
     *   ↓
     * Job Role
     *   ↓
     * Live Jobs
     */
    public function summary(
        IndustrialArea $area,
        ?int $companyId = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Base live-job query
        |--------------------------------------------------------------------------
        */
        $base = IndustrialJob::query()
            ->live()
            ->where(
                'industrial_area_id',
                $area->id
            );

        /*
        |--------------------------------------------------------------------------
        | Optional company context
        |--------------------------------------------------------------------------
        */
        if ($companyId) {
            $base->where(
                'industrial_company_id',
                $companyId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Department intelligence
        |--------------------------------------------------------------------------
        */
        $departmentCounts = (clone $base)
            ->selectRaw(
                'department_id, COUNT(*) as openings'
            )
            ->whereNotNull('department_id')
            ->groupBy('department_id')
            ->with('department')
            ->orderByDesc('openings')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Job-role intelligence
        |--------------------------------------------------------------------------
        */
        $departmentCounts = \App\Models\IndustrialDepartment::where('is_active',true)->orderBy('name')->get()->map(function($department)use($departmentCounts){$count=$departmentCounts->firstWhere('department_id',$department->id);return (object)['department_id'=>$department->id,'department'=>$department,'openings'=>$count?->openings??0];});
        $roleCounts = (clone $base)
            ->selectRaw(
                'job_role_id, COUNT(*) as openings'
            )
            ->whereNotNull('job_role_id')
            ->groupBy('job_role_id')
            ->with('role')
            ->orderByDesc('openings')
            ->limit(20)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Company intelligence
        |--------------------------------------------------------------------------
        */
        $companyCounts = (clone $base)
            ->selectRaw(
                'industrial_company_id, COUNT(*) as openings'
            )
            ->whereNotNull('industrial_company_id')
            ->groupBy('industrial_company_id')
            ->with('company')
            ->orderByDesc('openings')
            ->limit(20)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Popular skills
        |--------------------------------------------------------------------------
        |
        | Skills are stored as JSON on industrial_jobs.
        |
        | We calculate popularity from actual live jobs instead of maintaining
        | fake/manual counters.
        |
        */
        $skills = [];

        foreach (
            (clone $base)
                ->select([
                    'id',
                    'skills',
                ])
                ->lazyById(500) as $job
        ) {
            $jobSkills = $job->skills ?? [];

            if (! is_array($jobSkills)) {
                continue;
            }

            foreach ($jobSkills as $skill) {
                if (! is_string($skill)) {
                    continue;
                }

                $skill = mb_strtolower(
                    trim($skill)
                );

                if ($skill === '') {
                    continue;
                }

                $skills[$skill] =
                    ($skills[$skill] ?? 0) + 1;
            }
        }

        arsort($skills);

        /*
        |--------------------------------------------------------------------------
        | Qualification intelligence
        |--------------------------------------------------------------------------
        */
        $qualificationCounts = (clone $base)
            ->whereNotNull('qualification')
            ->selectRaw(
                'qualification, COUNT(*) as openings'
            )
            ->groupBy('qualification')
            ->orderByDesc('openings')
            ->limit(15)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Employment-type intelligence
        |--------------------------------------------------------------------------
        */
        $employmentTypeCounts = (clone $base)
            ->whereNotNull('employment_type')
            ->selectRaw(
                'employment_type, COUNT(*) as openings'
            )
            ->groupBy('employment_type')
            ->orderByDesc('openings')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Salary intelligence
        |--------------------------------------------------------------------------
        |
        | Keep this based on actual job records.
        |
        */
        $salaryStats = [
            'min' => null,
            'max' => null,
            'average_min' => null,
            'average_max' => null,
        ];

        $salaryQuery = (clone $base)
            ->whereNotNull('salary_min');

        if ($salaryQuery->exists()) {
            $salaryStats = [
                'min' => (clone $salaryQuery)->min(
                    'salary_min'
                ),

                'max' => (clone $salaryQuery)->max(
                    'salary_max'
                ),

                'average_min' => round(
                    (float) (
                        (clone $salaryQuery)
                        ->avg('salary_min')
                    ),
                    2
                ),

                'average_max' => round(
                    (float) (
                        (clone $salaryQuery)
                        ->avg('salary_max')
                    ),
                    2
                ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Live opening count
        |--------------------------------------------------------------------------
        */
        $liveCount = (clone $base)->count();

        /*
        |--------------------------------------------------------------------------
        | Return intelligence
        |--------------------------------------------------------------------------
        |
        | Existing keys are preserved so current Blade files continue working.
        |
        */
        return [
            'departmentCounts' =>
            $departmentCounts,

            'roleCounts' =>
            $roleCounts,

            'companyCounts' =>
            $companyCounts,

            'popularSkills' =>
            array_slice(
                $skills,
                0,
                16,
                true
            ),

            'qualificationCounts' =>
            $qualificationCounts,

            'employmentTypeCounts' =>
            $employmentTypeCounts,

            'salaryStats' =>
            $salaryStats,

            'liveCount' =>
            $liveCount,
        ];
    }

    /**
     * Find nearby industrial areas.
     *
     * Uses latitude / longitude when available.
     *
     * No state-specific hardcoding is used.
     */
    public function nearby(
        IndustrialArea $area
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Geographic search
        |--------------------------------------------------------------------------
        */
        if (
            $area->latitude !== null
            && $area->longitude !== null
        ) {
            $candidates = IndustrialArea::query()
                ->visible()
                ->where(
                    'id',
                    '<>',
                    $area->id
                )
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->with('state')
                ->get();

            $nearby = $candidates
                ->map(
                    function (
                        IndustrialArea $other
                    ) use ($area) {
                        $lat = deg2rad(
                            (float) $other->latitude
                                - (float) $area->latitude
                        );

                        $lng = deg2rad(
                            (float) $other->longitude
                                - (float) $area->longitude
                        );

                        $areaLatitude = deg2rad(
                            (float) $area->latitude
                        );

                        $otherLatitude = deg2rad(
                            (float) $other->latitude
                        );

                        $a =
                            sin($lat / 2) ** 2
                            +
                            cos($areaLatitude)
                            *
                            cos($otherLatitude)
                            *
                            sin($lng / 2) ** 2;

                        $a = min(
                            1,
                            max(0, $a)
                        );

                        $distance = 6371
                            * 2
                            * asin(
                                sqrt($a)
                            );

                        $other->distance_km =
                            round(
                                $distance,
                                1
                            );

                        return $other;
                    }
                )
                ->filter(
                    fn($other) =>
                    $other->distance_km <= 150
                )
                ->sortBy(
                    'distance_km'
                )
                ->take(8)
                ->values();

            return [
                'nearbyAreas' =>
                $nearby,

                'nearbyLabel' =>
                'Nearby industrial areas (within 150 km)',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback when coordinates are unavailable
        |--------------------------------------------------------------------------
        |
        | Stay within the same state rather than inventing geographical
        | relationships.
        |
        */
        $nearby = IndustrialArea::query()
            ->visible()
            ->where(
                'id',
                '<>',
                $area->id
            )
            ->where(
                'state_id',
                $area->state_id
            )
            ->with('state')
            ->orderBy('city')
            ->orderBy('name')
            ->limit(8)
            ->get();

        return [
            'nearbyAreas' =>
            $nearby,

            'nearbyLabel' =>
            'Explore more areas in '
                . ($area->state?->name ?? 'this state'),
        ];
    }

    /**
     * Generate a compact intelligence snapshot for an area.
     *
     * Useful for cards, APIs and dashboards.
     */
    public function snapshot(
        IndustrialArea $area
    ): array {
        $query = IndustrialJob::query()
            ->live()
            ->where(
                'industrial_area_id',
                $area->id
            );

        return [
            'area_id' =>
            $area->id,

            'area_name' =>
            $area->name,

            'state_name' =>
            $area->state?->name,

            'live_jobs' => (clone $query)->count(),

            'companies' => (clone $query)
                ->whereNotNull(
                    'industrial_company_id'
                )
                ->distinct(
                    'industrial_company_id'
                )
                ->count(
                    'industrial_company_id'
                ),

            'departments' => (clone $query)
                ->whereNotNull(
                    'department_id'
                )
                ->distinct(
                    'department_id'
                )
                ->count(
                    'department_id'
                ),

            'job_roles' => (clone $query)->whereNotNull('job_role_id')
                ->distinct(
                    'job_role_id'
                )
                ->count(
                    'job_role_id'
                ),
        ];
    }
}
