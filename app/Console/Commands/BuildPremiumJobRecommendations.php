<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BuildPremiumJobRecommendations extends Command
{
    protected $signature = 'premium:build-recommendations';
    protected $description = 'Build up to ten consent-gated job recommendations per active premium student';

    public function handle(): int
    {
        $campaigns = DB::table('premium_job_campaigns')
            ->where('is_active', true)
            ->whereDate('starts_at', '<=', today())
            ->whereDate('ends_at', '>=', today())
            ->get();

        foreach ($campaigns as $campaign) {
            $profile = DB::table('candidate_profiles')->where('user_id', $campaign->user_id)->first();
            $resume = DB::table('ats_resumes')->where('user_id', $campaign->user_id)->first();
            $skills = collect(json_decode($resume->skills ?? '[]', true))->filter()->take(12);
            $terms = $skills->merge([$profile->headline ?? null, $profile->preferred_role ?? null])->filter()->unique();

            $jobs = Job::query()->active()
                ->when($profile?->city, fn ($query, $city) => $query->orderByRaw('CASE WHEN LOWER(location) LIKE ? THEN 0 ELSE 1 END', ['%'.mb_strtolower($city).'%']))
                ->where(function ($query) use ($terms) {
                    foreach ($terms as $term) {
                        $query->orWhere('title', 'like', '%'.$term.'%')
                            ->orWhere('requirements', 'like', '%'.$term.'%');
                    }
                })
                ->latest('posted_at')
                ->limit((int) $campaign->daily_recommendation_limit)
                ->get();

            foreach ($jobs as $job) {
                $haystack = mb_strtolower($job->title.' '.json_encode($job->requirements));
                $matched = $terms->filter(fn ($term) => str_contains($haystack, mb_strtolower($term)))->count();
                $score = $terms->isEmpty() ? 0 : round(($matched / $terms->count()) * 100, 2);
                DB::table('premium_job_recommendations')->updateOrInsert(
                    ['campaign_id' => $campaign->id, 'job_id' => $job->id],
                    ['match_score' => $score, 'status' => 'recommended', 'recommended_on' => today(), 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        $this->info('Premium recommendations refreshed. Applications require separate candidate approval.');
        return self::SUCCESS;
    }
}
