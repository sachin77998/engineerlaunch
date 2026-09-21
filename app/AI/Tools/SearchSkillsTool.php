<?php

namespace App\AI\Tools;

use App\Models\IndustrialJobRole;
use App\Models\IndustrialJob;
use Illuminate\Database\Eloquent\Builder;

class SearchSkillsTool
{
    /**
     * Search skills already present in the platform's industrial job
     * roles and live industrial jobs.
     *
     * No skills are invented by this tool.
     */
    public function execute(array $filters = []): array
    {
        $search = trim((string) (
            $filters['search']
            ?? $filters['keyword']
            ?? $filters['query']
            ?? ''
        ));

        $limit = $this->normaliseLimit(
            $filters['limit'] ?? 30
        );

        $skills = [];

        /*
         * 1. Collect skills from role profiles.
         */
        $roleQuery = IndustrialJobRole::query()
            ->where('is_active', true)
            ->with('profile');

        if ($search !== '') {
            $this->applyRoleSearch(
                $roleQuery,
                $search
            );
        }

        $roles = $roleQuery
            ->limit(200)
            ->get();

        foreach ($roles as $role) {
            if (! $role->profile) {
                continue;
            }

            foreach (
                $this->normaliseList(
                    $role->profile->skills
                ) as $skill
            ) {
                $this->addSkill(
                    $skills,
                    $skill,
                    'role_profile',
                    $role->id,
                    $role->name
                );
            }
        }

        /*
         * 2. Collect skills from industrial jobs.
         *
         * This uses the existing industrial_jobs.skills JSON field.
         */
        $jobQuery = IndustrialJob::query()
            ->where('is_active', true);

        if (method_exists(IndustrialJob::class, 'scopeLive')) {
            $jobQuery->live();
        }

        if ($search !== '') {
            $this->applyJobSearch(
                $jobQuery,
                $search
            );
        }

        $jobs = $jobQuery
            ->limit(300)
            ->get();

        foreach ($jobs as $job) {
            foreach (
                $this->normaliseList(
                    $job->skills
                ) as $skill
            ) {
                $this->addSkill(
                    $skills,
                    $skill,
                    'live_job',
                    $job->id,
                    $job->job_title
                );
            }
        }

        /*
         * 3. Sort by number of occurrences.
         */
        usort(
            $skills,
            function (array $a, array $b) {
                if ($a['usage_count'] === $b['usage_count']) {
                    return strcmp(
                        $a['name'],
                        $b['name']
                    );
                }

                return $b['usage_count']
                    <=> $a['usage_count'];
            }
        );

        $skills = array_slice(
            $skills,
            0,
            $limit
        );

        return [
            'success' => true,
            'count' => count($skills),
            'filters' => $filters,
            'skills' => array_values($skills),
        ];
    }

    /**
     * Search role profiles when a skill/query is supplied.
     */
    protected function applyRoleSearch(
        Builder $query,
        string $search
    ): void {
        $terms = $this->tokens($search);

        if (empty($terms)) {
            return;
        }

        $query->where(function (Builder $builder) use ($terms) {
            foreach ($terms as $term) {
                $like = '%' . $term . '%';

                $builder->orWhereHas(
                    'profile',
                    function (Builder $profile) use ($like) {
                        $profile
                            ->where('skills', 'like', $like)
                            ->orWhere(
                                'qualifications',
                                'like',
                                $like
                            )
                            ->orWhere(
                                'summary',
                                'like',
                                $like
                            );
                    }
                );

                $builder->orWhere(
                    'name',
                    'like',
                    $like
                );
            }
        });
    }

    /**
     * Search industrial jobs when a skill/query is supplied.
     */
    protected function applyJobSearch(
        Builder $query,
        string $search
    ): void {
        $terms = $this->tokens($search);

        if (empty($terms)) {
            return;
        }

        $query->where(function (Builder $builder) use ($terms) {
            foreach ($terms as $term) {
                $like = '%' . $term . '%';

                $builder
                    ->orWhere(
                        'skills',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'job_title',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'qualification',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'description',
                        'like',
                        $like
                    );
            }
        });
    }

    /**
     * Add a skill to the aggregate collection.
     */
    protected function addSkill(
        array &$skills,
        string $skill,
        string $sourceType,
        int $sourceId,
        ?string $sourceName
    ): void {
        $skill = trim($skill);

        if ($skill === '') {
            return;
        }

        /*
         * Avoid treating very long descriptions as skills.
         */
        if (strlen($skill) > 150) {
            return;
        }

        $key = $this->normaliseSkillKey($skill);

        if ($key === '') {
            return;
        }

        if (! isset($skills[$key])) {
            $skills[$key] = [
                'name' => $skill,
                'slug' => $this->slug($skill),
                'usage_count' => 0,
                'sources' => [
                    'role_profile' => 0,
                    'live_job' => 0,
                ],
                'examples' => [],
            ];
        }

        $skills[$key]['usage_count']++;

        if (
            isset(
                $skills[$key]['sources'][$sourceType]
            )
        ) {
            $skills[$key]['sources'][$sourceType]++;
        }

        /*
         * Keep only a few real examples for the AI response.
         */
        if (
            count($skills[$key]['examples']) < 5
        ) {
            $example = [
                'id' => $sourceId,
                'name' => $sourceName,
            ];

            $alreadyExists = false;

            foreach (
                $skills[$key]['examples'] as $existing
            ) {
                if (
                    $existing['id'] === $sourceId &&
                    $existing['name'] === $sourceName
                ) {
                    $alreadyExists = true;
                    break;
                }
            }

            if (! $alreadyExists) {
                $skills[$key]['examples'][] = $example;
            }
        }
    }

    /**
     * Normalize a skill for comparison.
     */
    protected function normaliseSkillKey(
        string $skill
    ): string {
        $skill = strtolower(trim($skill));

        $skill = preg_replace(
            '/\s+/',
            ' ',
            $skill
        );

        return trim($skill);
    }

    /**
     * Convert a skill name into a stable slug.
     */
    protected function slug(
        string $value
    ): string {
        $value = strtolower(trim($value));

        $value = preg_replace(
            '/[^a-z0-9+#.]+/',
            '-',
            $value
        );

        return trim(
            (string) $value,
            '-'
        );
    }

    /**
     * Convert JSON/text/list data into an array.
     */
    protected function normaliseList(
        $value
    ): array {
        if (is_array($value)) {
            return array_values(
                array_filter(
                    $value,
                    function ($item) {
                        return is_string($item)
                            && trim($item) !== '';
                    }
                )
            );
        }

        if (
            is_string($value) &&
            trim($value) !== ''
        ) {
            $decoded = json_decode(
                $value,
                true
            );

            if (is_array($decoded)) {
                return array_values(
                    array_filter(
                        $decoded,
                        function ($item) {
                            return is_string($item)
                                && trim($item) !== '';
                        }
                    )
                );
            }

            return array_values(
                array_filter(
                    array_map(
                        'trim',
                        explode(',', $value)
                    )
                )
            );
        }

        return [];
    }

    /**
     * Tokenise natural-language search.
     */
    protected function tokens(
        string $value
    ): array {
        $tokens = preg_split(
            '/[^\p{L}\p{N}+#.]+/u',
            strtolower(trim($value)),
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        if (! is_array($tokens)) {
            return [];
        }

        $stopWords = [
            'a',
            'an',
            'and',
            'are',
            'at',
            'for',
            'from',
            'i',
            'in',
            'is',
            'me',
            'near',
            'of',
            'on',
            'the',
            'to',
            'with',
            'show',
            'find',
            'give',
            'please',
            'jobs',
            'job',
            'skills',
        ];

        $tokens = array_filter(
            $tokens,
            function ($token) use ($stopWords) {
                return strlen($token) >= 2
                    && ! in_array(
                        $token,
                        $stopWords,
                        true
                    );
            }
        );

        return array_values(
            array_unique(
                array_slice(
                    $tokens,
                    0,
                    12
                )
            )
        );
    }

    /**
     * Keep AI result sets bounded.
     */
    protected function normaliseLimit(
        $limit
    ): int {
        $limit = is_numeric($limit)
            ? (int) $limit
            : 30;

        return max(
            1,
            min($limit, 100)
        );
    }
}
