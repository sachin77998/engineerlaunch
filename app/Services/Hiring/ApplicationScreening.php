<?php

namespace App\Services\Hiring;

use App\Models\HiringScreeningRule;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApplicationScreening
{
    public const NOTICE_DAYS = ['immediate' => 0, '15 days' => 15, '1 month' => 30, '2 months' => 60, '3 months' => 90, 'more than 3 months' => 120];

    public function __construct(private Pipeline $pipeline) {}

    // Scores the application (skills 50, experience 20, location 15, salary 15) and applies the job's knockout rules.
    public function screen(JobApplication $application): JobApplication
    {
        $application->loadMissing(['job.skills', 'job.locations', 'job.screeningRule', 'candidate.candidateProfile']);
        $job = $application->job;
        $facts = $this->facts($application->candidate);
        [$score, $breakdown, $matched, $missing] = $this->score($job, $facts);
        $rule = $job->screeningRule?->is_active ? $job->screeningRule : null;
        $failed = $rule ? $this->knockouts($rule, $facts, $breakdown['skill_match']) : [];
        $decision = 'review';
        if ($rule && $failed && $rule->auto_reject) $decision = 'rejected';
        elseif ($rule && !$failed && $rule->auto_shortlist_score !== null && $score >= $rule->auto_shortlist_score) $decision = 'shortlisted';
        $application->forceFill(['match_score' => $score, 'matched_skills' => $matched, 'screened_at' => now(),
            'screening_result' => ['score' => $score, 'breakdown' => $breakdown, 'missing_skills' => $missing, 'failed' => $failed, 'decision' => $decision]])->saveQuietly();
        // Only untouched applications are moved automatically; a recruiter's decision is never overridden.
        if ($decision !== 'review' && in_array($application->stage ?? 'new', ['new', 'screening'], true)) {
            $this->pipeline->move($application, $decision, null, $decision === 'rejected' ? 'Auto-screen: ' . implode('; ', $failed) : 'Auto-screen: score ' . $score . ' met the shortlist bar.', $decision === 'rejected' ? mb_substr('Auto-screen: ' . $failed[0], 0, 120) : null);
        }
        return $application;
    }

    public function rescreenJob(Job $job): int
    {
        $count = 0;
        $job->applications()->whereIn('stage', ['new', 'screening'])->chunkById(200, function ($apps) use (&$count) {
            foreach ($apps as $app) { $this->screen($app); $count++; }
        });
        return $count;
    }

    public function facts(?User $user): array
    {
        $profile = $user?->candidateProfile;
        $skills = $profile ? DB::table('candidate_skills')->join('technologies', 'technologies.id', '=', 'candidate_skills.technology_id')->where('candidate_profile_id', $profile->id)->pluck('technologies.name')->all() : [];
        $resume = $profile ? DB::table('resumes')->where('candidate_profile_id', $profile->id)->orderByDesc('is_primary')->latest()->value('parsed_data') : null;
        $parsed = is_string($resume) ? json_decode($resume, true) : (array) $resume;
        $skills = array_merge($skills, (array) ($parsed['skills'] ?? []));
        return [
            'skills' => collect($skills)->filter(fn ($s) => is_string($s) && trim($s) !== '')->map(fn ($s) => mb_strtolower(trim($s)))->unique()->values()->all(),
            'experience' => $profile?->experience_years !== null ? (float) $profile->experience_years : null,
            'notice_days' => $this->noticeDays($profile?->notice_period),
            'expected_salary' => $profile?->expected_salary ? (float) $profile->expected_salary : null,
            'place' => mb_strtolower(trim(implode(' ', array_filter([$profile?->city, $profile?->state, $profile?->location])))),
            'phone_verified' => (bool) $profile?->phone_confirmed_at,
        ];
    }

    public function noticeDays(?string $notice): ?int
    {
        if ($notice === null || trim($notice) === '') return null;
        $key = mb_strtolower(trim($notice));
        if (isset(self::NOTICE_DAYS[$key])) return self::NOTICE_DAYS[$key];
        if (preg_match('/(\d+)\s*(day|week|month)/', $key, $m)) return (int) $m[1] * ['day' => 1, 'week' => 7, 'month' => 30][$m[2]];
        return null;
    }

    private function score(Job $job, array $f): array
    {
        $jobSkills = $job->skills->map(fn ($s) => ['name' => $s->name, 'key' => mb_strtolower($s->name), 'weight' => in_array($s->pivot->importance, ['mandatory', 'required'], true) ? 2 : 1]);
        // Whole-word match, so "Spring" matches "Spring Boot" but "Java" never matches "JavaScript".
        $word = fn ($needle, $hay) => strlen($needle) > 1 && preg_match('/(?<![\w+#.])' . preg_quote($needle, '/') . '(?![\w+#])/u', $hay) === 1;
        $has = fn ($key) => in_array($key, $f['skills'], true) || collect($f['skills'])->contains(fn ($c) => $word($key, $c) || $word($c, $key));
        $matched = $jobSkills->filter(fn ($s) => $has($s['key']));
        $total = $jobSkills->sum('weight');
        $skillPct = $total ? round($matched->sum('weight') / $total * 100) : null;

        $min = $job->experience_min; $max = $job->experience_max; $exp = $f['experience'];
        $expPct = $exp === null ? 40 : (($min === null && $max === null) ? 100 : (($min !== null && $exp < $min) ? max(0, 100 - ($min - $exp) * 35) : (($max !== null && $exp > $max) ? max(40, 100 - ($exp - $max) * 15) : 100)));

        $places = $job->locations->flatMap(fn ($l) => [$l->city, $l->state])->push($job->location, $job->state)->filter()->map(fn ($p) => mb_strtolower(trim($p)))->unique();
        $remote = in_array(mb_strtolower((string) $job->work_mode), ['remote', 'work from home'], true);
        $locPct = $remote || $places->isEmpty() ? 100 : ($f['place'] === '' ? 40 : ($places->contains(fn ($p) => str_contains($f['place'], $p)) ? 100 : ($job->relocation_allowed ? 60 : 20)));

        $cap = $job->salary_max ?: $job->salary_min; $want = $f['expected_salary'];
        $salPct = !$cap || !$want ? 70 : ($want <= $cap ? 100 : max(0, round(100 - ($want / $cap - 1) * 200)));

        $weights = ['skills' => 50, 'experience' => 20, 'location' => 15, 'salary' => 15];
        if ($skillPct === null) { unset($weights['skills']); }
        $parts = ['skills' => $skillPct, 'experience' => $expPct, 'location' => $locPct, 'salary' => $salPct];
        $score = round(collect($weights)->map(fn ($w, $k) => $parts[$k] * $w)->sum() / array_sum($weights));
        return [(int) $score, ['skill_match' => $skillPct, 'experience' => (int) $expPct, 'location' => (int) $locPct, 'salary' => (int) $salPct],
            $matched->pluck('name')->values()->all(), $jobSkills->reject(fn ($s) => $has($s['key']))->pluck('name')->values()->all()];
    }

    private function knockouts(HiringScreeningRule $rule, array $f, ?float $skillPct): array
    {
        $out = [];
        if ($rule->min_experience !== null && ($f['experience'] ?? -1) < $rule->min_experience) $out[] = 'experience below ' . (float) $rule->min_experience . ' yrs';
        if ($rule->max_experience !== null && $f['experience'] !== null && $f['experience'] > $rule->max_experience) $out[] = 'experience above ' . (float) $rule->max_experience . ' yrs';
        if ($rule->max_notice_days !== null && $f['notice_days'] !== null && $f['notice_days'] > $rule->max_notice_days) $out[] = 'notice period over ' . $rule->max_notice_days . ' days';
        if ($rule->max_expected_salary && $f['expected_salary'] && $f['expected_salary'] > $rule->max_expected_salary) $out[] = 'expected salary above budget';
        if ($rule->min_skill_match && $skillPct !== null && $skillPct < $rule->min_skill_match) $out[] = 'skill match ' . (int) $skillPct . '% below ' . $rule->min_skill_match . '%';
        if ($rule->locations && !collect($rule->locations)->contains(fn ($p) => $f['place'] !== '' && str_contains($f['place'], mb_strtolower($p)))) $out[] = 'location outside ' . implode(', ', $rule->locations);
        if ($rule->require_verified_phone && !$f['phone_verified']) $out[] = 'phone not verified';
        return $out;
    }
}
