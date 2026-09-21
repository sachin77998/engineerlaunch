<?php

namespace App\AI\Tools;

use App\Models\IndustrialDepartment;
use App\Models\IndustrialJobRole;
use Illuminate\Database\Eloquent\Builder;

class SearchRolesTool
{
    /**
     * Search the existing industrial job-role catalogue.
     *
     * This tool returns roles already present in the platform.
     * It does not invent roles.
     */
    public function execute(array $filters = []): array
    {
        $query = IndustrialJobRole::query();

        $query->where('is_active', true);

        $query->with([
            'department',
            'profile',
            'aliases',
            'processes',
            'sectors',
        ]);

        $this->applySearchFilter($query, $filters);
        $this->applyDepartmentFilter($query, $filters);
        $this->applySectorFilter($query, $filters);
        $this->applyProcessFilter($query, $filters);

        $limit = $this->normaliseLimit(
            $filters['limit'] ?? 20
        );

        $roles = $query
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'count' => $roles->count(),
            'filters' => $filters,
            'roles' => $roles
                ->map(function (IndustrialJobRole $role) {
                    return $this->formatRole($role);
                })
                ->values()
                ->all(),
        ];
    }

    /**
     * Search role name, slug, aliases and role profile information.
     */
    protected function applySearchFilter(
        Builder $query,
        array $filters
    ): void {
        $search = trim((string) (
            $filters['search']
            ?? $filters['keyword']
            ?? $filters['query']
            ?? ''
        ));

        if ($search === '') {
            return;
        }

        $terms = $this->tokens($search);

        if (empty($terms)) {
            return;
        }

        $query->where(function (Builder $builder) use ($terms) {
            foreach ($terms as $term) {
                $like = '%' . $term . '%';

                $builder
                    ->orWhere('name', 'like', $like)
                    ->orWhere('slug', 'like', $like);

                /*
                 * Role aliases.
                 */
                $builder->orWhereHas(
                    'aliases',
                    function (Builder $alias) use ($like) {
                        $alias->where(
                            'alias',
                            'like',
                            $like
                        );
                    }
                );

                /*
                 * Department name.
                 */
                $builder->orWhereHas(
                    'department',
                    function (Builder $department) use ($like) {
                        $department
                            ->where('name', 'like', $like)
                            ->orWhere('category', 'like', $like);
                    }
                );

                /*
                 * Role profile summary, skills and qualifications.
                 *
                 * skills and qualifications are stored as JSON/text in the
                 * existing role-profile structure.
                 */
                $builder->orWhereHas(
                    'profile',
                    function (Builder $profile) use ($like) {
                        $profile
                            ->where('summary', 'like', $like)
                            ->orWhere('skills', 'like', $like)
                            ->orWhere(
                                'qualifications',
                                'like',
                                $like
                            );
                    }
                );

                /*
                 * Process and sector names.
                 */
                $builder->orWhereHas(
                    'processes',
                    function (Builder $process) use ($like) {
                        $process->where(
                            'name',
                            'like',
                            $like
                        );
                    }
                );

                $builder->orWhereHas(
                    'sectors',
                    function (Builder $sector) use ($like) {
                        $sector->where(
                            'name',
                            'like',
                            $like
                        );
                    }
                );
            }
        });
    }

    /**
     * Filter by department.
     */
    protected function applyDepartmentFilter(
        Builder $query,
        array $filters
    ): void {
        $departmentId = $filters['department_id'] ?? null;

        if (
            $departmentId !== null &&
            is_numeric($departmentId)
        ) {
            $query->where(
                'department_id',
                (int) $departmentId
            );

            return;
        }

        $department = trim((string) (
            $filters['department']
            ?? $filters['department_name']
            ?? ''
        ));

        if ($department === '') {
            return;
        }

        $query->whereHas(
            'department',
            function (Builder $builder) use ($department) {
                $like = '%' . $department . '%';

                $builder
                    ->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('category', 'like', $like);
            }
        );
    }

    /**
     * Filter by industrial sector.
     */
    protected function applySectorFilter(
        Builder $query,
        array $filters
    ): void {
        $sectorId = $filters['sector_id'] ?? null;

        if (
            $sectorId !== null &&
            is_numeric($sectorId)
        ) {
            $query->whereHas(
                'sectors',
                function (Builder $builder) use ($sectorId) {
                    $builder->where(
                        'industrial_sectors.id',
                        (int) $sectorId
                    );
                }
            );

            return;
        }

        $sector = trim((string) (
            $filters['sector']
            ?? $filters['industry']
            ?? ''
        ));

        if ($sector === '') {
            return;
        }

        $query->whereHas(
            'sectors',
            function (Builder $builder) use ($sector) {
                $builder->where(
                    'name',
                    'like',
                    '%' . $sector . '%'
                );
            }
        );
    }

    /**
     * Filter by industrial process.
     */
    protected function applyProcessFilter(
        Builder $query,
        array $filters
    ): void {
        $processId = $filters['process_id'] ?? null;

        if (
            $processId !== null &&
            is_numeric($processId)
        ) {
            $query->whereHas(
                'processes',
                function (Builder $builder) use ($processId) {
                    $builder->where(
                        'industrial_processes.id',
                        (int) $processId
                    );
                }
            );

            return;
        }

        $process = trim((string) (
            $filters['process']
            ?? ''
        ));

        if ($process === '') {
            return;
        }

        $query->whereHas(
            'processes',
            function (Builder $builder) use ($process) {
                $builder->where(
                    'name',
                    'like',
                    '%' . $process . '%'
                );
            }
        );
    }

    /**
     * Format an industrial role for the AI layer.
     */
    protected function formatRole(
        IndustrialJobRole $role
    ): array {
        $profile = $role->profile;
        $department = $role->department;

        return [
            'id' => $role->id,

            'name' => $role->name,

            'slug' => $role->slug,

            'department' => $department
                ? $department->name
                : null,

            'department_id' => $role->department_id,

            'department_category' => $department
                ? $department->category
                : null,

            'aliases' => $role->aliases
                ? $role->aliases
                ->map(function ($alias) {
                    /*
                         * Support both common naming conventions without
                         * assuming an exact alias schema.
                         */
                    return $alias->alias
                        ?? $alias->name
                        ?? null;
                })
                ->filter()
                ->values()
                ->all()
                : [],

            'skills' => $profile
                ? $this->normaliseList(
                    $profile->skills
                )
                : [],

            'qualifications' => $profile
                ? $this->normaliseList(
                    $profile->qualifications
                )
                : [],

            'summary' => $profile
                ? $profile->summary
                : null,

            'processes' => $role->processes
                ? $role->processes
                ->pluck('name')
                ->filter()
                ->values()
                ->all()
                : [],

            'sectors' => $role->sectors
                ? $role->sectors
                ->pluck('name')
                ->filter()
                ->values()
                ->all()
                : [],

            'is_active' => (bool) $role->is_active,

            'verification_status' => $role->verification_status,

            'source_name' => $role->source_name,

            'source_url' => $role->source_url,

            'last_verified_at' => $role->last_verified_at
                ? $role->last_verified_at->toDateTimeString()
                : null,
        ];
    }

    /**
     * Normalize JSON/text/list values.
     */
    protected function normaliseList($value): array
    {
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

            /*
             * Support comma-separated values if an older record contains
             * plain text instead of JSON.
             */
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
    protected function tokens(string $value): array
    {
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
     * Keep result sets bounded.
     */
    protected function normaliseLimit($limit): int
    {
        $limit = is_numeric($limit)
            ? (int) $limit
            : 20;

        return max(
            1,
            min($limit, 50)
        );
    }
}
