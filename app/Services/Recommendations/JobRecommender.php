<?php

namespace App\Services\Recommendations;

use App\Models\Job;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Content-based job recommender.
 *
 * 1. Interest profile: skills from what the person searched, viewed, applied to and told the assistant, each signal
 *    weighted by type and decayed by age (half-life 7 days), then expanded with related skills from the SkillGraph.
 * 2. Candidate retrieval: recent active jobs mentioning any profile skill (SQL REGEXP), or title words as a fallback.
 * 3. Ranking: TF-IDF-weighted cosine similarity between the profile and each job's skills, plus experience fit,
 *    title-word overlap and freshness. Each result carries a match percentage and the reasons behind it.
 */
class JobRecommender
{
    private const SIGNAL_WEIGHTS = ['apply' => 3.0, 'assistant' => 2.5, 'view' => 1.5, 'search' => 1.2];
    private const STOP_WORDS = ['and', 'or', 'the', 'for', 'with', 'job', 'jobs', 'in', 'of', 'a', 'an', 'to', 'at', 'developer', 'engineer', 'senior', 'junior', 'remote'];

    public function __construct(private SkillGraph $graph) {}

    /**
     * @param array<int, array{kind:string, value:string, at?:int}> $signals
     * @param array{experience?:?int, location?:?string, exclude?:array<int>, limit?:int} $options
     * @return array{profile:array, related:array, jobs:Collection}
     */
    public function recommend(array $signals, array $options = []): array
    {
        [$profile, $words] = $this->profile($signals);
        $direct = $profile;
        // Expand with related skills at a discount, so a C++ search also surfaces C / Embedded C openings.
        $related = [];
        foreach (array_slice($direct, 0, 5, true) as $skill => $weight) {
            foreach ($this->graph->related($skill) as $other => $strength) {
                if (isset($direct[$other])) continue;
                $related[$other] = max($related[$other] ?? 0, $weight * $strength * 0.6);
            }
        }
        arsort($related);
        $related = array_slice($related, 0, 8, true);
        $vector = $direct + $related;
        if (!$vector && !$words) return ['profile' => [], 'related' => [], 'jobs' => collect()];

        $candidates = $this->candidates(array_keys($vector), $words, $options);
        $experience = $options['experience'] ?? null;
        $norm = sqrt(array_sum(array_map(fn ($skill, $w) => ($w * $this->graph->idf($skill)) ** 2, array_keys($vector), $vector))) ?: 1;

        $scored = $candidates->map(function (Job $job) use ($vector, $norm, $words, $experience, $direct) {
            $jobSkills = $this->graph->skillsIn($job->title . ' ' . $job->title . ' ' . $this->text($job));
            $dot = 0.0;
            $jobNorm = 0.0;
            foreach ($jobSkills as $skill => $count) {
                $tf = (1 + log($count)) * $this->graph->idf($skill);
                $jobNorm += $tf ** 2;
                if (isset($vector[$skill])) $dot += $tf * $vector[$skill] * $this->graph->idf($skill);
            }
            $cosine = $jobNorm > 0 ? $dot / (sqrt($jobNorm) * $norm) : 0.0;
            $titleWords = array_unique(preg_split('/[^a-z0-9+#.]+/', mb_strtolower($job->title)));
            $overlap = $words ? count(array_intersect($titleWords, array_keys($words))) / count($words) : 0;
            [$fit, $range] = $this->experienceFit($job, $experience);
            $ageDays = $job->posted_at ? max(0, $job->posted_at->diffInDays(now())) : 60;
            $fresh = exp(-$ageDays / 30);
            $matched = array_values(array_intersect(array_keys($jobSkills), array_keys($direct)));
            // Coverage: share of what the person asked for that this job mentions ("PHP + Laravel" beats "PHP" alone).
            $coverage = $direct ? array_sum(array_intersect_key($direct, array_flip($matched))) / array_sum($direct) : 0;
            $score = 0.42 * $cosine + 0.2 * $coverage + 0.15 * min(1, $overlap) + 0.15 * $fit + 0.08 * $fresh;

            // Only a related skill and nothing the person asked for: still useful, but below direct matches.
            if (!$matched && $overlap == 0) $score *= 0.55;
            $similar = array_values(array_diff(array_intersect(array_keys($jobSkills), array_keys($vector)), $matched));
            $reasons = [];
            if ($matched) $reasons[] = 'Matches ' . implode(', ', array_slice($matched, 0, 3));
            if ($similar) $reasons[] = 'Related: ' . implode(', ', array_slice($similar, 0, 2));
            if ($experience !== null && $range) $reasons[] = $fit >= 0.99 ? 'Fits your ' . $experience . ' yrs experience' : 'Asks ' . $range;
            if ($ageDays <= 3) $reasons[] = 'Posted recently';

            return ['job' => $job, 'score' => $score, 'match' => (int) round(min(0.99, 0.35 + $score) * 100), 'reasons' => $reasons, 'experience' => $range];
        })->filter(fn ($row) => $row['score'] > 0.05)->sortByDesc('score')->values();

        // Diversity: at most two openings from one company in the first page of recommendations.
        $perCompany = [];
        $jobs = $scored->filter(function ($row) use (&$perCompany) {
            $company = $row['job']->company_id;
            return ($perCompany[$company] = ($perCompany[$company] ?? 0) + 1) <= 2;
        })->take($options['limit'] ?? 12)->values();

        return ['profile' => array_slice($direct, 0, 8, true), 'related' => $related, 'jobs' => $jobs];
    }

    /** Skill weights and free-text words from the signals. @return array{0:array<string,float>,1:array<string,float>} */
    public function profile(array $signals): array
    {
        $skills = [];
        $words = [];
        $now = time();
        foreach (array_slice($signals, -60) as $signal) {
            $value = trim((string) ($signal['value'] ?? ''));
            if ($value === '') continue;
            $age = max(0, $now - (int) ($signal['at'] ?? $now)) / 86400;
            $weight = (self::SIGNAL_WEIGHTS[$signal['kind'] ?? 'search'] ?? 1.0) * 0.5 ** ($age / 7);
            $found = $this->graph->skillsIn($value);
            foreach ($found as $skill => $count) $skills[$skill] = ($skills[$skill] ?? 0) + $weight;
            if (in_array($signal['kind'] ?? '', ['search', 'assistant'], true)) {
                foreach (preg_split('/[^a-z0-9+#.]+/', mb_strtolower($value)) as $word) {
                    if (mb_strlen($word) < 2 || in_array($word, self::STOP_WORDS, true) || is_numeric($word)) continue;
                    $words[$word] = ($words[$word] ?? 0) + $weight;
                }
            }
        }
        arsort($skills);
        arsort($words);
        $max = max($skills ?: [1]);
        return [array_map(fn ($w) => round($w / $max, 3), $skills), array_slice($words, 0, 6, true)];
    }

    /** Experience range a job asks for, inferred from structured fields or its text. @return array{0:?int,1:?int} */
    public function experienceRange(Job $job): array
    {
        if ($job->experience_min !== null || $job->experience_max !== null) return [$job->experience_min, $job->experience_max];
        $text = mb_strtolower($job->title . ' ' . mb_substr($this->text($job), 0, 1500));
        if (preg_match('/(\d{1,2})\s*(?:\+|-|–|to)\s*(\d{1,2})?\s*(?:\+\s*)?(?:years|yrs|year)/', $text, $m)) {
            return [(int) $m[1], isset($m[2]) && $m[2] !== '' ? (int) $m[2] : null];
        }
        if (preg_match('/\b(fresher|freshers|graduate trainee|trainee|intern|internship|entry[- ]level|campus|new grad)\b/', $text)) return [0, 1];
        if (preg_match('/\b(junior|jr\.?|associate)\b/', $text)) return [0, 3];
        if (preg_match('/\b(principal|architect|head of|director|vp|vice president)\b/', $text)) return [10, null];
        if (preg_match('/\b(lead|manager|staff)\b/', $text)) return [6, null];
        if (preg_match('/\b(senior|sr\.?)\b/', $text)) return [4, null];
        return [null, null];
    }

    /** @return array{0:float,1:?string} fit 0..1 and a readable range */
    private function experienceFit(Job $job, ?int $years): array
    {
        [$min, $max] = $this->experienceRange($job);
        $range = $min === null && $max === null ? null : ($max === null ? ($min . '+ yrs') : ($min . '-' . $max . ' yrs'));
        if ($years === null || $range === null) return [0.5, $range];
        if ($years >= ($min ?? 0) && ($max === null || $years <= $max)) return [1.0, $range];
        $gap = $years < ($min ?? 0) ? ($min - $years) : ($years - $max);
        return [max(0.0, 1 - $gap / 3), $range];
    }

    private function candidates(array $skills, array $words, array $options): Collection
    {
        $aliases = [];
        foreach (config('skill_taxonomy', []) as $list) {
            foreach ($list as $skill => $names) if (in_array($skill, $skills, true)) array_push($aliases, ...$names);
        }
        $aliases = array_values(array_unique(array_filter($aliases, fn ($a) => mb_strlen($a) > 1 || $a === 'c')));
        $query = Job::active()->with('company:id,name,slug,logo_url')
            ->select(['id', 'company_id', 'title', 'slug', 'location', 'requirements', 'experience_min', 'experience_max', 'posted_at', 'source', 'posting_source', 'salary_min', 'salary_max', 'salary_currency', 'work_mode']);
        $driver = DB::connection()->getDriverName();
        $query->where(function ($q) use ($aliases, $words, $driver) {
            if ($aliases && in_array($driver, ['mysql', 'mariadb'], true)) {
                $pattern = '(^|[^a-z0-9+#])(' . implode('|', array_map(fn ($a) => preg_quote(mb_strtolower($a), '~'), array_slice($aliases, 0, 60))) . ')($|[^a-z0-9+#])';
                $q->orWhereRaw('LOWER(title) REGEXP ?', [$pattern])->orWhereRaw('LOWER(CAST(requirements AS CHAR)) REGEXP ?', [$pattern]);
            } else {
                foreach (array_slice($aliases, 0, 30) as $alias) $q->orWhere('title', 'like', '%' . $alias . '%');
            }
            foreach (array_keys($words) as $word) $q->orWhere('title', 'like', '%' . addcslashes($word, '%_\\') . '%');
        });
        if (!empty($options['exclude'])) $query->whereNotIn('id', $options['exclude']);
        if (filled($options['location'] ?? null)) $query->where('location', 'like', '%' . addcslashes(trim($options['location']), '%_\\') . '%');
        return $query->latest('posted_at')->limit(300)->get();
    }

    private function text(Job $job): string
    {
        $requirements = $job->requirements;
        return is_array($requirements) ? implode(' ', array_map(fn ($r) => is_array($r) ? implode(' ', $r) : (string) $r, $requirements)) : (string) $requirements;
    }

    /** Public JSON shape used by the widgets and the assistant. */
    public static function present(array $row): array
    {
        $job = $row['job'];
        return [
            'id' => $job->id, 'title' => $job->title, 'company' => $job->company?->name, 'logo' => $job->company?->logo_url,
            'location' => $job->location, 'posted' => $job->posted_at?->diffForHumans(null, true),
            'url' => $job->slug ? route('jobs.show', $job->slug) : route('opportunities.show', $job->id),
            'match' => $row['match'], 'reasons' => $row['reasons'], 'experience' => $row['experience'],
            'via' => $job->posting_source === 'job_board' ? Str::ucfirst((string) $job->source) : null,
        ];
    }
}
