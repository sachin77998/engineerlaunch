<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

/** Role families from config/job_roles.php: query filter, title detection and live counts. */
class JobRoles
{
    /** Slugs a role covers (itself plus its 'includes'). */
    public static function family(string $slug): array
    {
        $roles = config('job_roles', []);
        return array_values(array_unique(array_merge([$slug], $roles[$slug]['includes'] ?? [])));
    }

    /** Restricts a jobs query to a role: stored role_family or a title keyword. */
    public static function apply(Builder $query, string $slug): Builder
    {
        $roles = config('job_roles', []);
        $slugs = self::family($slug);
        $keywords = collect($slugs)->flatMap(fn ($s) => $roles[$s]['keywords'] ?? [])->unique()->values();
        return $query->where(function ($q) use ($slugs, $keywords) {
            $q->whereIn('role_family', $slugs);
            foreach ($keywords as $keyword) $q->orWhere('title', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%');
        });
    }

    /** Most specific role for a job title (umbrella roles are never assigned). */
    public static function detect(string $title): ?string
    {
        $title = ' ' . mb_strtolower($title) . ' ';
        // Specific families first so "AI chatbot developer" is a chatbot role, not generic software development.
        foreach (['chatbot-ai', 'cyber-security', 'ai-ml', 'data-science', 'quality-testing', 'devops-cloud', 'full-stack', 'frontend', 'backend', 'software-development'] as $slug) {
            foreach (config("job_roles.{$slug}.keywords", []) as $keyword) {
                if (str_contains($title, $keyword)) return $slug;
            }
        }
        return null;
    }

    /**
     * Cached active job counts per role, for the homepage chips. Never computed in a web request: the scheduler
     * refreshes them (refreshCounts); until then the chips show no numbers.
     */
    public static function counts(): array
    {
        return Cache::get('job-roles:counts', []);
    }

    /**
     * Counts every role in one pass over active job titles (seconds, instead of one LIKE-heavy query per role).
     * A job counts for each role whose keywords match, the same rule as the filter.
     */
    public static function refreshCounts(): array
    {
        $roles = config('job_roles', []);
        $counts = array_fill_keys(array_keys($roles), 0);
        \App\Models\Job::active()->select(['jobs.id', 'jobs.title', 'jobs.role_family'])->chunkById(5000, function ($jobs) use ($roles, &$counts) {
            foreach ($jobs as $job) {
                $title = ' ' . mb_strtolower((string) $job->title) . ' ';
                foreach ($roles as $slug => $role) {
                    foreach (self::family($slug) as $member) {
                        $hit = $job->role_family === $member;
                        foreach ($roles[$member]['keywords'] ?? [] as $keyword) if ($hit || ($hit = str_contains($title, $keyword))) break;
                        if ($hit) { $counts[$slug]++; break; }
                    }
                }
            }
        }, 'jobs.id', 'id');
        Cache::put('job-roles:counts', $counts, now()->addDays(2));
        return $counts;
    }
}
