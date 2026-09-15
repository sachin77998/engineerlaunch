<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IngestJobsRequest;
use App\Jobs\ProcessIngestedJob;
use App\Services\Kafka\KafkaPublisher;
use Illuminate\Http\JsonResponse;

class JobIngestionController extends Controller
{
    public function store(IngestJobsRequest $request, KafkaPublisher $kafka): JsonResponse
    {
        $jobs = $request->validated('jobs');
        $messages = collect($jobs)->map(fn (array $job) => ['type' => 'job.ingestion.requested', 'data' => ['job' => $job], 'key' => $job['external_job_id']])->all();
        $transport = $kafka->publishMany(config('kafka.topics.job_ingestion'), $messages) ? 'kafka' : 'database';
        if ($transport === 'database') foreach ($jobs as $job) ProcessIngestedJob::dispatch($job)->onQueue('ingestion');
        return response()->json(['accepted' => count($jobs), 'status' => 'queued', 'transport' => $transport], 202);
    }
}
