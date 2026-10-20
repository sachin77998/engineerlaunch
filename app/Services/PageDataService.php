<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Job;
use App\Models\Technology;

class PageDataService
{
    public function about(): array
    {
        return [
            // Counted over every job row: cached like the homepage stats.
            'stats' => \Illuminate\Support\Facades\Cache::remember('about:stats', now()->addMinutes(30), fn () => [
                'jobs' => Job::active()->count(),
                'companies' => Company::active()->whereHas('activeJobs')->count(),
            ]),
            'technologies' => Technology::query()->orderBy('name')->pluck('name'),
        ];
    }
}
