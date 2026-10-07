<?php

namespace App\Jobs;

use App\Services\JobIngestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessIngestedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public int $timeout = 120;

    public function __construct(public array $payload)
    {
        $this->onConnection('database');
        $this->onQueue('ingestion');
        $this->afterCommit();
    }

    public function handle(JobIngestionService $service): void
    {
        $service->ingest($this->payload);
    }
}
