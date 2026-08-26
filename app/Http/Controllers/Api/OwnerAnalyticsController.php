<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OwnerAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OwnerAnalyticsController extends Controller
{
    public function __invoke(Request $request, OwnerAnalyticsService $analytics): JsonResponse
    {
        $filters = $request->validate([
            'city' => ['nullable', 'string', 'max:100'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'gte:salary_min'],
        ]);

        return response()->json(['data' => $analytics->summary($filters)]);
    }
}
