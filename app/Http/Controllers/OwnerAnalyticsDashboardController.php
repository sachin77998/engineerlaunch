<?php

namespace App\Http\Controllers;

use App\Services\OwnerAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OwnerAnalyticsDashboardController extends Controller
{
    public function __invoke(Request $request, OwnerAnalyticsService $analytics): View
    {
        $filters = $request->validate([
            'city' => ['nullable', 'string', 'max:100'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'gte:salary_min'],
        ]);

        return view('admin.analytics', ['analytics' => $analytics->summary($filters)]);
    }
}
