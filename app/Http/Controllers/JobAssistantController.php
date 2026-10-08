<?php

namespace App\Http\Controllers;

use App\Services\JobSkillVocabulary;
use App\Services\SkillRecommender;
use Illuminate\Http\Request;

/** Homepage job assistant: "What have you done?" -> detected skills -> matching live openings. */
class JobAssistantController extends Controller
{
    public function recommend(Request $request, SkillRecommender $recommender)
    {
        $data = $request->validate(['text' => 'required|string|max:600', 'location' => 'nullable|string|max:80']);
        $location = $data['location'] ?? null;
        $skills = $recommender->detect($data['text']);

        if ($skills) {
            [$jobs, $total] = $recommender->jobs($skills, $location);
        } else {
            // Unknown words still search titles, descriptions and skills the normal way.
            $query = \App\Models\Job::active()->with('company:id,name,slug');
            JobSkillVocabulary::apply($query, $data['text']);
            if (filled($location)) $query->where('location', 'like', '%' . addcslashes(trim($location), '%_\\') . '%');
            $total = (clone $query)->count();
            $jobs = $query->latest('posted_at')->limit(8)->get();
        }

        $searchUrl = fn (string $q) => url('/') . '?' . http_build_query(array_filter(['q' => $q, 'location' => $location])) . '#jobs';
        return response()->json([
            'skills' => array_map(fn ($s) => [
                'skill' => $s['skill'], 'field' => $s['field'], 'count' => $recommender->countFor($s, $location), 'url' => $searchUrl($s['aliases'][0]),
            ], $skills),
            'jobs' => $jobs->map(fn ($job) => [
                'title' => $job->title, 'company' => $job->company?->name, 'location' => $job->location,
                'posted' => $job->posted_at?->diffForHumans(null, true),
                'url' => $job->slug ? route('jobs.show', $job->slug) : route('opportunities.show', $job->id),
                'via' => $job->posting_source === 'job_board' ? ucfirst((string) $job->source) : null,
            ])->values(),
            'total' => $total,
            'search_url' => $searchUrl($skills ? $skills[0]['aliases'][0] : $data['text']),
        ]);
    }
}
