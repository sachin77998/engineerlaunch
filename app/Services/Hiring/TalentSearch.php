<?php

namespace App\Services\Hiring;

use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

// The employer's permanent applicant database: every application to their jobs, filterable across any period.
class TalentSearch
{
    public const PERIODS = ['30d' => 'Last 30 days', '90d' => 'Last 3 months', '6m' => 'Last 6 months', '1y' => 'Last 1 year', '3y' => 'Last 3 years', '5y' => 'Last 5 years', 'all' => 'All time'];
    public const SORTS = ['newest' => 'Newest first', 'oldest' => 'Oldest first', 'score' => 'Best match', 'experience' => 'Most experience', 'salary' => 'Lowest expected CTC', 'rating' => 'Highest rated'];
    public const NOTICE = ['0' => 'Immediate', '15' => 'Up to 15 days', '30' => 'Up to 1 month', '60' => 'Up to 2 months', '90' => 'Up to 3 months'];
    public const FIELDS = ['q', 'job_id', 'stage', 'period', 'from', 'to', 'exp_min', 'exp_max', 'ctc_min', 'ctc_max', 'cur_max', 'notice', 'location', 'skills', 'skill_mode', 'score_min', 'rating_min', 'tag', 'source', 'verified', 'education', 'company', 'unique', 'sort'];

    public function query(User $user, array $f): Builder
    {
        $q = JobApplication::query()->select('job_applications.*')
            ->join('jobs', 'jobs.id', '=', 'job_applications.job_id')
            ->join('users as u', 'u.id', '=', 'job_applications.user_id')
            ->leftJoin('candidate_profiles as cp', 'cp.user_id', '=', 'job_applications.user_id')
            ->with(['candidate.candidateProfile', 'job:id,title,slug']);
        $team = app(Team::class)->userIds($user);
        if ($user->role !== 'admin') $q->whereIn('jobs.employer_id', $team);

        if ($s = trim((string) ($f['q'] ?? ''))) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $s) . '%';
            $q->where(fn ($w) => $w->where('u.name', 'like', $like)->orWhere('u.email', 'like', $like)->orWhere('cp.phone', 'like', $like)->orWhere('cp.headline', 'like', $like)
                ->orWhere('cp.current_designation', 'like', $like)->orWhere('cp.current_company', 'like', $like)->orWhere('cp.preferred_role', 'like', $like)->orWhere('jobs.title', 'like', $like));
        }
        if (!empty($f['job_id'])) $q->where('job_applications.job_id', (int) $f['job_id']);
        if ($stages = array_filter((array) ($f['stage'] ?? []))) $q->whereIn('job_applications.stage', $stages);

        $from = !empty($f['from']) ? $f['from'] : match ($f['period'] ?? 'all') {
            '30d' => now()->subDays(30), '90d' => now()->subMonths(3), '6m' => now()->subMonths(6), '1y' => now()->subYear(), '3y' => now()->subYears(3), '5y' => now()->subYears(5), default => null,
        };
        if ($from) $q->where('job_applications.applied_at', '>=', $from);
        if (!empty($f['to'])) $q->where('job_applications.applied_at', '<', \Carbon\Carbon::parse($f['to'])->addDay());

        if (is_numeric($f['exp_min'] ?? null)) $q->where('cp.experience_years', '>=', (float) $f['exp_min']);
        if (is_numeric($f['exp_max'] ?? null)) $q->where('cp.experience_years', '<=', (float) $f['exp_max']);
        if (is_numeric($f['ctc_min'] ?? null)) $q->where('cp.expected_salary', '>=', (float) $f['ctc_min']);
        if (is_numeric($f['ctc_max'] ?? null)) $q->where('cp.expected_salary', '<=', (float) $f['ctc_max']);
        if (is_numeric($f['cur_max'] ?? null)) $q->where('cp.current_salary', '<=', (float) $f['cur_max']);
        if (isset($f['notice']) && $f['notice'] !== '' && is_numeric($f['notice'])) {
            $allowed = collect(ApplicationScreening::NOTICE_DAYS)->filter(fn ($d) => $d <= (int) $f['notice'])->keys()->all();
            $q->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(cp.notice_period)'), $allowed);
        }
        if ($loc = trim((string) ($f['location'] ?? ''))) {
            $q->where(fn ($w) => collect(preg_split('/\s*,\s*/', $loc))->filter()->each(fn ($p) => $w->orWhere('cp.city', 'like', "%$p%")->orWhere('cp.state', 'like', "%$p%")->orWhere('cp.location', 'like', "%$p%")));
        }
        if ($skills = collect(preg_split('/\s*,\s*/', (string) ($f['skills'] ?? '')))->filter()->take(15)->all()) {
            $one = fn ($w, $skill) => $w->where(fn ($x) => $x
                ->whereExists(fn ($e) => $e->selectRaw(1)->from('candidate_skills as cs')->join('technologies as t', 't.id', '=', 'cs.technology_id')->whereColumn('cs.candidate_profile_id', 'cp.id')->where('t.name', 'like', $skill))
                ->orWhere('job_applications.matched_skills', 'like', '%"' . addcslashes($skill, '"%_') . '"%')
                ->orWhereExists(fn ($e) => $e->selectRaw(1)->from('resumes as r')->whereColumn('r.candidate_profile_id', 'cp.id')->where('r.parsed_data', 'like', '%"' . addcslashes($skill, '"%_') . '"%')));
            ($f['skill_mode'] ?? 'any') === 'all'
                ? collect($skills)->each(fn ($s) => $one($q, $s))
                : $q->where(fn ($w) => collect($skills)->each(fn ($s) => $w->orWhere(fn ($x) => $one($x, $s))));
        }
        if (is_numeric($f['score_min'] ?? null)) $q->where('job_applications.match_score', '>=', (float) $f['score_min']);
        if (is_numeric($f['rating_min'] ?? null)) $q->where('job_applications.rating', '>=', (int) $f['rating_min']);
        if ($tag = trim((string) ($f['tag'] ?? ''))) $q->where('job_applications.tags', 'like', '%"' . addcslashes($tag, '"%_') . '"%');
        if (!empty($f['source'])) $f['source'] === 'portal' ? $q->whereNull('job_applications.application_source') : $q->where('job_applications.application_source', $f['source']);
        if ($edu = trim((string) ($f['education'] ?? ''))) $q->where('cp.education', 'like', "%$edu%");
        if ($co = trim((string) ($f['company'] ?? ''))) $q->where('cp.current_company', 'like', "%$co%");
        match ($f['verified'] ?? '') {
            'phone' => $q->whereNotNull('cp.phone_confirmed_at'),
            'email' => $q->whereNotNull('u.email_verified_at'),
            'pan', 'aadhaar', 'uan' => $q->whereExists(fn ($e) => $e->selectRaw(1)->from('candidate_verifications as v')->whereColumn('v.candidate_id', 'job_applications.user_id')->where('v.type', $f['verified'])->where('v.status', 'passed')),
            default => null,
        };
        // One row per candidate: keep only their latest application to this employer.
        if (!empty($f['unique'])) {
            $q->whereRaw('job_applications.id = (select max(ja2.id) from job_applications ja2 join jobs j2 on j2.id = ja2.job_id where ja2.user_id = job_applications.user_id' . ($user->role !== 'admin' ? ' and j2.employer_id in (' . implode(',', array_map('intval', $team)) . ')' : '') . ')');
        }
        match ($f['sort'] ?? 'newest') {
            'oldest' => $q->orderBy('job_applications.applied_at'),
            'score' => $q->orderByDesc('job_applications.match_score'),
            'experience' => $q->orderByDesc('cp.experience_years'),
            'salary' => $q->orderByRaw('cp.expected_salary is null, cp.expected_salary'),
            'rating' => $q->orderByRaw('job_applications.rating is null, job_applications.rating desc'),
            default => $q->orderByDesc('job_applications.applied_at'),
        };
        return $q->orderByDesc('job_applications.id');
    }

    public function owns(User $user, JobApplication $application): bool
    {
        return $user->role === 'admin' || in_array((int) $application->job?->employer_id, app(Team::class)->userIds($user), true);
    }
}
