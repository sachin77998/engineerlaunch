<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IngestJobsRequest;
use App\Jobs\ProcessIngestedJob;
use Illuminate\Http\JsonResponse;

class JobIngestionController extends Controller
{
    public function store(IngestJobsRequest $request): JsonResponse
    {
        $jobs = $request->validated('jobs');
        foreach ($jobs as $job) {
            ProcessIngestedJob::dispatch($job)->onQueue('ingestion');
        }

        return response()->json(['accepted' => count($jobs), 'status' => 'queued'], 202);
    }
}
