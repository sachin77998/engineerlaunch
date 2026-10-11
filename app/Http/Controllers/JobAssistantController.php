<?php

namespace App\Http\Controllers;

use App\Services\JobSkillVocabulary;
use App\Services\Recommendations\JobRecommender;
use App\Services\SkillRecommender;
use Illuminate\Http\Request;

/**
 * Homepage job assistant: education, passout batch, experience and skills -> detected skills -> live openings ranked
 * by the recommender (match % with reasons).
 */
class JobAssistantController extends Controller
{
    // Degree branch -> searchable starting skills when the person has not listed any yet.
    private const BRANCH_SKILLS = [
        '/\b(cse|computer|it\b|information technology|mca|bca|software)/i' => 'software developer java python javascript sql',
        '/\b(ece|electronics|communication|instrumentation)/i' => 'embedded c vlsi electronics',
        '/\b(eee|electrical)/i' => 'electrical plc scada',
        '/\b(mech|mechanical|production|automobile)/i' => 'mechanical autocad production quality',
        '/\b(civil)/i' => 'civil site engineer autocad',
        '/\b(chemical)/i' => 'chemical process',
        '/\b(b\.?com|commerce|accounts|ca\b|mba finance)/i' => 'accounting gst tally',
        '/\b(pharm|b\.?sc|biotech)/i' => 'pharma quality control',
        '/\b(iti|diploma)/i' => 'technician operator cnc',
    ];

    public function recommend(Request $request, SkillRecommender $recommender, JobRecommender $ranker)
    {
        $data = $request->validate([
            'text' => 'required|string|max:600', 'location' => 'nullable|string|max:80',
            'education' => 'nullable|string|max:120', 'passout_year' => 'nullable|integer|min:1970|max:2035',
            'experience' => 'nullable|integer|min:0|max:50',
        ]);
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

        // Experience: what the person said, otherwise years since the passout batch (capped, they may be a fresher).
        $experience = $data['experience'] ?? null;
        $passout = $data['passout_year'] ?? null;
        if ($experience === null && $passout) $experience = max(0, min(now()->year - $passout, 30));
        $signals = [['kind' => 'assistant', 'value' => $data['text'], 'at' => time()]];
        $education = trim((string) ($data['education'] ?? ''));
        if ($education !== '') {
            foreach (self::BRANCH_SKILLS as $pattern => $keywords) {
                if (preg_match($pattern, $education)) { $signals[] = ['kind' => 'search', 'value' => $keywords, 'at' => time() - 86400 * 7]; break; }
            }
        }
        $ranked = $ranker->recommend($signals, ['experience' => $experience, 'location' => $location, 'limit' => 8]);

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
            'recommended' => $ranked['jobs']->map(fn ($row) => JobRecommender::present($row))->values(),
            'related_skills' => array_keys(array_slice($ranked['related'], 0, 5, true)),
            'experience' => $experience,
            'career_note' => $this->careerNote($experience, $passout),
        ]);
    }

    private function careerNote(?int $years, ?int $passout): ?string
    {
        if ($years === null) return null;
        $batch = $passout ? "For the {$passout} batch " : 'With your experience ';
        return $batch . match (true) {
            $years === 0 => '(fresher): look for Graduate Engineer Trainee, Associate, Trainee and Intern roles, campus drives and PSU GATE recruitment.',
            $years <= 2 => "(about {$years} yrs): Junior / Associate Engineer and Software Engineer roles asking 0-2 or 1-3 years fit best.",
            $years <= 5 => "(about {$years} yrs): Software Engineer, Senior Engineer and Specialist roles asking 2-5 or 3-6 years fit best.",
            $years <= 9 => "(about {$years} yrs): Senior, Lead and Team Lead roles asking 5+ years fit best.",
            default => "(about {$years} yrs): Lead, Architect, Manager and Head roles asking 8+ years fit best.",
        };
    }
}
