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
            'stats' => [
                'jobs' => Job::active()->count(),
                'companies' => Company::active()->whereHas('activeJobs')->count(),
            ],
            'technologies' => Technology::query()->orderBy('name')->pluck('name'),
        ];
    }
}
