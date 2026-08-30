<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Job;
use App\Models\Technology;
use App\Support\DiscoveryCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $stats = Cache::remember(
            DiscoveryCache::key('home.stats'),
            DiscoveryCache::ttl(),
            fn (): array => [
                'total_jobs' => Job::active()->count(),
                'total_companies' => Company::active()->whereHas('activeJobs')->count(),
                'total_technologies' => Technology::query()->count(),
            ]
        );

        return view('portal-v2', [
            'dsaTracks' => config('interview.dsa', []),
            'homeStats' => $stats,
        ]);
    }
}
