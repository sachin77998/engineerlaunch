<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Support\Facades\Cache;

/** Detects skills in free text (any field, see config/skill_taxonomy.php) and finds matching live openings. */
class SkillRecommender
{
    // Too ambiguous inside job titles ("CA" = California, "MR" = Mister).
    private const SEARCH_EXCLUDE = ['ca', 'mr', 'gc', 'sta', 'cam', 'js', 'ev', 'ml', 'go'];

    /** @return array<int, array{skill:string, field:string, aliases:array}> */
    public function detect(string $text): array
    {
        $text = ' ' . mb_strtolower($text) . ' ';
        $aliases = [];
        foreach (config('skill_taxonomy', []) as $field => $skills) {
            foreach ($skills as $skill => $list) {
                foreach ($list as $alias) $aliases[] = [$alias, $skill, $field, $list];
            }
        }
        usort($aliases, fn ($a, $b) => mb_strlen($b[0]) <=> mb_strlen($a[0]));
        $found = [];
        foreach ($aliases as [$alias, $skill, $field, $list]) {
            if (isset($found[$skill])) continue;
            if (preg_match('/(?<![\pL\pN+#.])' . preg_quote($alias, '/') . '(?![\pL\pN+#])/u', $text)) {
                $found[$skill] = ['skill' => $skill, 'field' => $field, 'aliases' => $list];
                // Remove the match so "c" inside an already-matched "embedded c" is not counted twice.
                $text = preg_replace('/(?<![\pL\pN+#.])' . preg_quote($alias, '/') . '(?![\pL\pN+#])/u', ' ', $text);
            }
            if (count($found) >= 8) break;
        }
        return array_values($found);
    }

    public function jobs(array $skills, ?string $location = null, int $limit = 8): array
    {
        $query = Job::active()->with('company:id,name,slug');
        $query->where(function ($q) use ($skills) {
            foreach ($skills as $skill) $this->whereSkill($q, $skill['aliases']);
        });
        if (filled($location)) $query->where('location', 'like', '%' . addcslashes(trim($location), '%_\\') . '%');
        $total = (clone $query)->count();
        $jobs = $query->latest('posted_at')->limit($limit)->get(['id', 'company_id', 'title', 'slug', 'location', 'posted_at', 'source', 'posting_source']);
        return [$jobs, $total];
    }

    public function countFor(array $skill, ?string $location = null): int
    {
        return Cache::remember('skill-count:' . md5($skill['skill'] . '|' . $location), now()->addMinutes(20), function () use ($skill, $location) {
            $query = Job::active()->where(fn ($q) => $this->whereSkill($q, $skill['aliases']));
            if (filled($location)) $query->where('location', 'like', '%' . addcslashes(trim($location), '%_\\') . '%');
            return $query->count();
        });
    }

    private function whereSkill($query, array $aliases): void
    {
        $aliases = array_values(array_filter($aliases, fn ($a) => !in_array($a, self::SEARCH_EXCLUDE, true) && ($a === 'c' || mb_strlen($a) > 1)));
        if (!$aliases) return;
        $driver = $query->getConnection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            foreach ($aliases as $alias) $query->orWhere('title', 'like', '%' . $alias . '%');
            return;
        }
        $operator = $driver === 'pgsql' ? '~*' : 'REGEXP';
        $pattern = '(^|[^a-z0-9+#])(' . implode('|', array_map(fn ($a) => preg_quote($a, '~'), $aliases)) . ')($|[^a-z0-9+#])';
        $cast = $driver === 'pgsql' ? 'TEXT' : 'CHAR';
        $query->orWhereRaw("LOWER(title) {$operator} ?", [$pattern])
            ->orWhereRaw("LOWER(COALESCE(CAST(requirements AS {$cast}), '')) {$operator} ?", [$pattern]);
    }
}
