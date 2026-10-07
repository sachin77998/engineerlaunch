<?php

namespace App\Observers;

use App\Jobs\MatchResumeToHrJobs;
use App\Models\Resume;

class ResumeObserver
{
    public function updated(Resume $resume): void
    {
        if ($resume->wasChanged('parsing_status') && $resume->parsing_status === 'processed') {
            MatchResumeToHrJobs::dispatch($resume->id);
        }
    }
}
