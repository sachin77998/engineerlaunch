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

    /** Active job counts per role (cached for an hour), for the homepage role chips. */
    public static function counts(): array
    {
        return Cache::remember('job-roles:counts', now()->addHour(), function () {
            $counts = [];
            foreach (array_keys(config('job_roles', [])) as $slug) {
                $counts[$slug] = self::apply(\App\Models\Job::active(), $slug)->count();
            }
            return $counts;
        });
    }
}
