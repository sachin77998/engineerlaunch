<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps job_search (one compact row per open job) in step with the jobs table. A job saved through the model is copied
 * at once; bulk imports that write the table directly are picked up by `jobs:search-index` every few minutes.
 */
class JobSearchIndex
{
    private const CHECKPOINT = 'job-search:checkpoint';
    private const READY = 'job-search:ready';
    private const COLUMNS = ['id', 'company_id', 'title', 'location', 'country', 'state', 'role_family', 'department', 'work_mode', 'job_type', 'experience_level',
        'experience_min', 'experience_max', 'posting_source', 'source', 'salary_min', 'salary_max', 'salary_currency', 'salary_period', 'views', 'posted_at', 'expires_at'];

    /** Whether searches can use the copy (it exists and has been filled). */
    public function ready(): bool
    {
        return (bool) Cache::remember(self::READY, 600, fn () => rescue(fn () => Schema::hasTable('job_search') && DB::table('job_search')->exists(), false, false));
    }

    /** Copies (or removes) the jobs updated since the last run; everything when $full. Returns [copied, removed]. */
    public function sync(bool $full = false, ?\Closure $progress = null): array
    {
        $started = now();
        $since = $full ? null : Cache::get(self::CHECKPOINT);
        $copied = 0; $removed = 0;
        $jobs = DB::table('jobs')->select('id')->when($since, fn ($q) => $q->where('updated_at', '>=', $since));
        $jobs->chunkById(1000, function ($chunk) use (&$copied, &$removed, $progress) {
            [$c, $r] = $this->copy($chunk->pluck('id')->all());
            $copied += $c; $removed += $r;
            if ($progress) $progress($copied, $removed);
        });
        if ($full) {
            // Rows whose job no longer exists at all.
            DB::table('job_search')->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('jobs')->whereColumn('jobs.id', 'job_search.id'))->delete();
        }
        // Expired or closed without an update (rare): checked against the open-jobs rule on every run.
        $removed += DB::table('job_search')->whereNotNull('expires_at')->where('expires_at', '<=', now())->delete();
        // Two minutes of overlap so rows written while this ran are not missed.
        Cache::forever(self::CHECKPOINT, $started->copy()->subMinutes(2)->toDateTimeString());
        Cache::forget(self::READY);
        return [$copied, $removed];
    }

    /** Re-copies these jobs: open ones are written, the rest removed. Returns [copied, removed]. */
    public function copy(array $ids): array
    {
        if ($ids === []) return [0, 0];
        $open = Job::query()->withoutGlobalScopes()->active()->whereIntegerInRaw('id', $ids)->whereNull('deleted_at')
            ->get(array_merge(self::COLUMNS, ['role', 'category', 'requirements', 'description']));
        $companies = $open->isEmpty() ? collect() : DB::table('companies')->whereIntegerInRaw('id', $open->pluck('company_id')->filter()->unique()->values()->all())->pluck('name', 'id');
        $open->each(fn ($job) => $job->setAttribute('company_name', $companies[$job->company_id] ?? null));
        $technologies = $open->isEmpty() ? collect() : DB::table('job_technology')->join('technologies', 'technologies.id', '=', 'job_technology.technology_id')
            ->whereIntegerInRaw('job_technology.job_id', $open->pluck('id')->all())->get(['job_technology.job_id', 'technologies.name'])->groupBy('job_id');
        $rows = $open->map(function ($job) use ($technologies) {
            $row = [];
            foreach (self::COLUMNS as $column) $row[$column] = $job->getRawOriginal($column);
            $row['title'] = mb_substr((string) $row['title'], 0, 255);
            $row['views'] = max(0, (int) $row['views']);
            $row['search_text'] = $this->text([$job->title, $job->company_name, $job->location, $job->state, $job->country, $job->department, $job->role, $job->category,
                $technologies->get($job->id, collect())->pluck('name')->implode(' '),
                mb_substr($this->plain($job->requirements), 0, 300), mb_substr($this->plain($job->description), 0, 600)]);
            return $row;
        });
        foreach ($rows->chunk(500) as $chunk) DB::table('job_search')->upsert($chunk->values()->all(), ['id'], array_diff(array_keys($chunk->first()), ['id']));
        $closed = array_values(array_diff($ids, $open->pluck('id')->all()));
        $removed = $closed ? DB::table('job_search')->whereIntegerInRaw('id', $closed)->delete() : 0;
        return [$rows->count(), $removed];
    }

    public function remove(int $id): void
    {
        rescue(fn () => DB::table('job_search')->where('id', $id)->delete(), null, false);
    }

    private function plain(mixed $html): string
    {
        // Requirements are stored as a list on some jobs.
        if (is_array($html)) $html = implode(' ', array_filter(\Illuminate\Support\Arr::flatten($html), 'is_scalar'));
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private function text(array $parts): string
    {
        $text = ' ' . self::normalize(implode(' ', array_filter(array_map(fn ($p) => trim((string) $p), $parts)))) . ' ';
        // Each skill's canonical name is added when a spelling of it appears ("cpp" -> also "c++"), so a search reads the
        // text once instead of once per spelling.
        foreach (JobSkillVocabulary::GROUPS as $canonical => $variants) {
            if (str_contains($text, ' ' . $canonical . ' ')) continue;
            foreach ($variants as $variant) if (str_contains($text, ' ' . self::normalize($variant) . ' ')) { $text .= $canonical . ' '; break; }
        }
        return $text;
    }

    /**
     * Lower case, words separated by single spaces (+ # . kept inside words: c++, c#, node.js). The stored text is padded
     * with spaces, so LIKE '% java %' finds the word java and not javascript.
     */
    public static function normalize(string $text): string
    {
        $text = preg_replace('/[^\pL\pN+#.]+/u', ' ', mb_strtolower($text));
        $text = preg_replace('/\.+(?=\s|$)|(?<=\s|^)\.+/u', ' ', $text);
        return trim(preg_replace('/\s+/u', ' ', $text));
    }
}
