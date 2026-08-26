<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class OwnerAnalyticsService
{
    public function summary(array $filters = []): array
    {
        $applications = DB::table('job_applications as a')
            ->join('candidate_profiles as p', 'p.user_id', '=', 'a.user_id');

        if (! empty($filters['city'])) {
            $applications->whereRaw('LOWER(p.location) LIKE ?', ['%'.mb_strtolower($filters['city']).'%']);
        }

        if (isset($filters['salary_min'])) {
            $applications->where('p.expected_salary', '>=', $filters['salary_min']);
        }

        if (isset($filters['salary_max'])) {
            $applications->where('p.expected_salary', '<=', $filters['salary_max']);
        }

        $cities = ['Mohali', 'Chandigarh', 'Pune', 'Delhi', 'Gurugram', 'Noida'];
        $cityCounts = collect($cities)->mapWithKeys(fn (string $city) => [
            $city => DB::table('job_applications as a')
                ->join('candidate_profiles as p', 'p.user_id', '=', 'a.user_id')
                ->whereRaw('LOWER(p.location) LIKE ?', ['%'.mb_strtolower($city).'%'])
                ->count(),
        ])->all();

        $salaryBands = [
            'Up to ₹5 Lakh' => [null, 500000],
            '₹5–10 Lakh' => [500000, 1000000],
            '₹10–20 Lakh' => [1000000, 2000000],
            'Above ₹20 Lakh' => [2000000, null],
        ];
        $salaryCounts = collect($salaryBands)->mapWithKeys(function (array $range, string $label) {
            $query = DB::table('job_applications as a')
                ->join('candidate_profiles as p', 'p.user_id', '=', 'a.user_id')
                ->whereNotNull('p.expected_salary');
            if ($range[0] !== null) $query->where('p.expected_salary', '>=', $range[0]);
            if ($range[1] !== null) $query->where('p.expected_salary', '<=', $range[1]);
            return [$label => $query->count()];
        })->all();

        $salaryStats = DB::table('candidate_profiles')->whereNotNull('expected_salary')
            ->selectRaw('COUNT(*) as candidates, SUM(expected_salary) as total, AVG(expected_salary) as average, MIN(expected_salary) as minimum, MAX(expected_salary) as maximum')
            ->first();

        return [
            'filters' => $filters,
            'students' => User::where(fn ($q) => $q->where('role_code', 1)->orWhere('role', 'student'))->count(),
            'hr_accounts' => User::where(fn ($q) => $q->where('role_code', 0)->orWhere('role', 'employer'))->count(),
            'applications' => (clone $applications)->count(),
            'new_applications' => (clone $applications)->where('a.status', 'new')->count(),
            'city_counts' => $cityCounts,
            'salary_counts' => $salaryCounts,
            'salary_statistics' => $salaryStats,
            'application_statuses' => DB::table('job_applications')->selectRaw('status, COUNT(*) as total')->groupBy('status')->orderByDesc('total')->get(),
        ];
    }
}
