<?php

namespace App\Jobs;

use App\Models\Resume;
use App\Services\ResumeJobMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MatchResumeToHrJobs implements ShouldQueue
{
    use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;

    public int $tries = 3;

    public int $timeout = 240;

    public array $backoff = [30, 120, 300];

    public function __construct(public int $resumeId)
    {
        $this->onQueue('resume-processing');
        $this->afterCommit();
    }

    public function handle(ResumeJobMatchingService $service): void
    {
        $resume = Resume::find($this->resumeId);
        if (! $resume) {
            return;
        }

        if (in_array($resume->parsing_status, ['queued', 'processing'], true)) {
            $this->release(20);

            return;
        }

        $service->process($resume);
    }
}
