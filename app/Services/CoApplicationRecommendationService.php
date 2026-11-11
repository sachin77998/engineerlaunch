<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// "Candidates who applied to this job also applied to": walks the applicant <-> job bipartite graph.
// A shared applicant who applies everywhere counts for less (1 / log(2 + their applications)), and the total
// is divided by sqrt(the other job's applicants) so jobs that everyone applies to don't win by default.
class CoApplicationRecommendationService
{
    private const MAX_APPLICANTS = 500;  // most recent applicants of the source job that are walked
    private const MIN_SHARED = 2;        // never surface a job linked through a single person

    public function for(Job $source, ?int $userId = null, int $limit = 10): Collection
    {
        $rankedIds = Cache::remember('co-applied-jobs:v1:'.$source->id, now()->addMinutes(30), fn () => $this->rank($source->id));
        if (empty($rankedIds)) return collect();

        $query = Job::query()->active()->whereIn('id', $rankedIds)->with(['company:id,name,logo_url', 'skills:id,name']);
        if ($userId) $query->whereDoesntHave('applications', fn ($applications) => $applications->where('user_id', $userId)->where('status', '!=', 'withdrawn'));
        $jobs = $query->get()->keyBy('id');

        return collect($rankedIds)->map(fn ($id) => $jobs->get($id))->filter(fn ($job) => $job && filled($job->slug))->take($limit)->values();
    }

    private function rank(int $jobId): array
    {
        $userIds = $this->applications()->where('job_id', $jobId)->latest('applied_at')->limit(self::MAX_APPLICANTS)->pluck('user_id')->all();
        if (count($userIds) < self::MIN_SHARED) return [];

        $userDegree = $this->applications()->whereIn('user_id', $userIds)->groupBy('user_id')->selectRaw('user_id, count(*) as n')->pluck('n', 'user_id');
        $edges = $this->applications()->whereIn('user_id', $userIds)->where('job_id', '!=', $jobId)->get(['job_id', 'user_id']);

        $score = [];
        $shared = [];
        foreach ($edges as $edge) {
            $score[$edge->job_id] = ($score[$edge->job_id] ?? 0) + 1 / log(2 + (int) ($userDegree[$edge->user_id] ?? 1));
            $shared[$edge->job_id] = ($shared[$edge->job_id] ?? 0) + 1;
        }
        $candidates = array_keys(array_filter($shared, fn ($n) => $n >= self::MIN_SHARED));
        if (!$candidates) return [];

        $jobDegree = $this->applications()->whereIn('job_id', $candidates)->groupBy('job_id')->selectRaw('job_id, count(*) as n')->pluck('n', 'job_id');
        $ranked = [];
        foreach ($candidates as $candidate) $ranked[$candidate] = $score[$candidate] / sqrt(max(1, (int) ($jobDegree[$candidate] ?? 1)));
        arsort($ranked);

        return array_slice(array_keys($ranked), 0, 40);
    }

    // Only applications a candidate chose to send: withdrawn ones and resume auto-matches say nothing about interest.
    private function applications()
    {
        return DB::table('job_applications')->where('status', '!=', 'withdrawn')->whereRaw("coalesce(application_source, 'candidate') <> 'resume_auto_match'");
    }
}
