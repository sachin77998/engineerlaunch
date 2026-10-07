<?php

namespace App\Console\Commands;

use App\Jobs\MatchResumeToHrJobs;
use App\Models\Resume;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunCandidateAutoApply extends Command
{
    protected $signature = 'candidates:auto-apply';

    protected $description = 'Queue matching for consented candidates with processed primary resumes';

    public function handle(): int
    {
        $queued = 0;
        DB::table('candidate_auto_apply_preferences')
            ->where('enabled', true)
            ->whereNotNull('consented_at')
            ->chunkById(100, function ($preferences) use (&$queued) {
                foreach ($preferences as $preference) {
                    $resume = Resume::whereHas('profile', fn ($query) => $query->where('user_id', $preference->user_id))
                        ->where('is_primary', true)
                        ->where('parsing_status', 'processed')
                        ->latest('parsed_at')
                        ->first();
                    if ($resume) {
                        MatchResumeToHrJobs::dispatch($resume->id);
                        $queued++;
                    }
                }
            });
        $this->info("Queued {$queued} resumes for matching and candidate-authorized internal applications.");

        return self::SUCCESS;
    }
}
