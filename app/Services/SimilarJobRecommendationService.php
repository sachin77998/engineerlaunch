<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SimilarJobRecommendationService
{
    public function for(Job $source, ?int $userId = null, int $limit = 15): Collection
    {
        $source->loadMissing('skills');
        $skillIds = $source->skills->pluck('id')->values();

        $rankedIds = Cache::remember(
            'similar-jobs:v3:'.$source->id.':'.($source->updated_at?->timestamp ?? 0),
            now()->addMinutes(30),
            function () use ($source, $skillIds) {
                $candidates = Job::query()
                    ->active()
                    ->where('jobs.id', '!=', $source->id)
                    ->where(function ($query) use ($source, $skillIds) {
                        if ($skillIds->isNotEmpty()) {
                            $query->orWhereHas('skills', fn ($skills) => $skills->whereIn('skills.id', $skillIds));
                        }
                        if ($source->role_family) $query->orWhere('role_family', $source->role_family);
                        if ($source->department) $query->orWhere('department', $source->department);
                        if ($source->engineering_discipline) $query->orWhere('engineering_discipline', $source->engineering_discipline);
                        if ($source->category) $query->orWhere('category', $source->category);
                        if ($source->primary_technology) $query->orWhere('primary_technology', $source->primary_technology);
                    })
                    ->with(['company:id,name,logo_url', 'skills:id,name'])
                    ->latest('posted_at')
                    ->limit(250)
                    ->get();

                return $candidates->map(function (Job $candidate) use ($source, $skillIds) {
                    $sharedSkills = $candidate->skills->pluck('id')->intersect($skillIds)->count();
                    $score = $sharedSkills * 20;
                    $score += $source->role_family && $candidate->role_family === $source->role_family ? 18 : 0;
                    $score += $source->department && $candidate->department === $source->department ? 16 : 0;
                    $score += $source->engineering_discipline && $candidate->engineering_discipline === $source->engineering_discipline ? 8 : 0;
                    $score += $source->category && $candidate->category === $source->category ? 14 : 0;
                    $score += $source->primary_technology && $candidate->primary_technology === $source->primary_technology ? 18 : 0;
                    $score += $source->work_mode && $candidate->work_mode === $source->work_mode ? 5 : 0;
                    $score += $source->location && $candidate->location === $source->location ? 7 : 0;

                    return ['id' => $candidate->id, 'score' => $score];
                })->filter(fn ($match) => $match['score'] > 0)
                    ->sortByDesc('score')->take(40)->pluck('id')->values()->all();
            }
        );

        if (empty($rankedIds)) return collect();

        $query = Job::query()->active()->whereIn('id', $rankedIds)
            ->with(['company:id,name,logo_url', 'skills:id,name']);
        if ($userId) $query->whereDoesntHave('applications', fn ($applications) => $applications->where('user_id', $userId)->where('status', '!=', 'withdrawn'));
        $jobs = $query->get()->keyBy('id');

        return collect($rankedIds)->map(fn ($id) => $jobs->get($id))->filter()->take($limit)->values();
    }
}
