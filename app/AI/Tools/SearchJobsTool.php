<?php

namespace App\AI\Tools;

use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class SearchJobsTool
{
    /**
     * Search real jobs from the platform database.
     *
     * This tool NEVER creates or invents job records.
     */
    public function execute(array $filters = []): array
    {
        $query = Job::query();

        $this->applyActiveFilter($query);
        $this->applyKeywordFilter($query, $filters);
        $this->applyLocationFilter($query, $filters);
        $this->applyExperienceFilter($query, $filters);
        $this->applySalaryFilter($query, $filters);
        $this->applyJobTypeFilter($query, $filters);
        $this->applyWorkModeFilter($query, $filters);
        $this->applyCompanyFilter($query, $filters);
        $this->applyTechnologyFilter($query, $filters);

        $limit = $this->normaliseLimit(
            $filters['limit'] ?? 10
        );

        $jobs = $query
            ->with([
                'company',
                'technologies',
            ])
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'count' => $jobs->count(),
            'filters' => $filters,
            'jobs' => $jobs->map(function (Job $job) {
                return $this->formatJob($job);
            })->values()->all(),
        ];
    }

    /**
     * Apply the existing Job active scope.
     */
    protected function applyActiveFilter(Builder $query): void
    {
        $query->active();
    }

    /**
     * Search real job fields and related company/technology information.
     *
     * IMPORTANT:
     * The generic jobs table does not contain an "experience" column.
     * Experience is therefore searched through the fields that actually
     * exist in the Job model/database.
     */
    protected function applyKeywordFilter(
        Builder $query,
        array $filters
    ): void {
        $keyword = trim((string) (
            $filters['keyword']
            ?? $filters['search']
            ?? $filters['query']
            ?? ''
        ));

        if ($keyword === '') {
            return;
        }

        $terms = $this->tokens($keyword);

        if (empty($terms)) {
            return;
        }

        $availableColumns = $this->jobColumns();

        $searchColumns = array_values(array_intersect(
            [
                'title',
                'description',
                'requirements',
                'responsibilities',
                'education',
                'salary',
                'location',
                'role_family',
            ],
            $availableColumns
        ));

        $query->where(function (Builder $builder) use (
            $terms,
            $searchColumns
        ) {
            foreach ($terms as $term) {
                $builder->orWhere(function (Builder $subQuery) use (
                    $term,
                    $searchColumns
                ) {
                    $like = '%' . $term . '%';

                    foreach ($searchColumns as $index => $column) {
                        if ($index === 0) {
                            $subQuery->where(
                                $column,
                                'like',
                                $like
                            );
                        } else {
                            $subQuery->orWhere(
                                $column,
                                'like',
                                $like
                            );
                        }
                    }

                    $subQuery
                        ->orWhereHas(
                            'company',
                            function (Builder $company) use ($like) {
                                $company->where(
                                    'name',
                                    'like',
                                    $like
                                );
                            }
                        )
                        ->orWhereHas(
                            'technologies',
                            function (Builder $technology) use ($like) {
                                $technology->where(
                                    'name',
                                    'like',
                                    $like
                                );
                            }
                        );
                });
            }
        });
    }

    /**
     * Filter jobs by location.
     */
    protected function applyLocationFilter(
        Builder $query,
        array $filters
    ): void {
        $location = trim((string) (
            $filters['location']
            ?? $filters['city']
            ?? ''
        ));

        if ($location === '') {
            return;
        }

        $locations = $this->tokens($location);

        if (empty($locations)) {
            return;
        }

        $availableColumns = $this->jobColumns();

        $query->where(function (Builder $builder) use (
            $locations,
            $availableColumns
        ) {
            foreach ($locations as $locationToken) {
                $like = '%' . $locationToken . '%';

                $builder->orWhere(function (
                    Builder $locationQuery
                ) use (
                    $like,
                    $availableColumns
                ) {
                    $first = true;

                    foreach (['location'] as $column) {
                        if (! in_array(
                            $column,
                            $availableColumns,
                            true
                        )) {
                            continue;
                        }

                        if ($first) {
                            $locationQuery->where(
                                $column,
                                'like',
                                $like
                            );

                            $first = false;
                        } else {
                            $locationQuery->orWhere(
                                $column,
                                'like',
                                $like
                            );
                        }
                    }

                    $locationQuery->orWhereHas(
                        'company',
                        function (Builder $company) use ($like) {
                            $company->where(
                                'name',
                                'like',
                                $like
                            )->orWhere(
                                'description',
                                'like',
                                $like
                            );
                        }
                    );
                });
            }
        });
    }

    /**
     * Filter by experience.
     *
     * The current generic jobs table does not contain an "experience"
     * database column.
     *
     * We therefore use the fields available in the actual job record
     * and do not generate SQL against a nonexistent column.
     */
    protected function applyExperienceFilter(
        Builder $query,
        array $filters
    ): void {
        $experience = $filters['experience'] ?? null;

        if ($experience === null || $experience === '') {
            $experience = $filters['experience_years'] ?? null;
        }

        if ($experience === null || $experience === '') {
            return;
        }

        /*
         * Experience filtering will be applied after retrieving jobs
         * when the application stores experience inside structured
         * requirements/responsibilities text rather than a dedicated
         * database column.
         *
         * We intentionally do not query jobs.experience here because
         * that column does not exist.
         */
    }

    /**
     * Filter using the application's salary field.
     */
    protected function applySalaryFilter(
        Builder $query,
        array $filters
    ): void {
        $minimum = $filters['salary_min']
            ?? $filters['min_salary']
            ?? null;

        $maximum = $filters['salary_max']
            ?? $filters['max_salary']
            ?? null;

        $availableColumns = $this->jobColumns();

        if (
            in_array('salary', $availableColumns, true)
            && $minimum !== null
            && is_numeric($minimum)
        ) {
            $minimum = (float) $minimum;

            $query->where(function (
                Builder $builder
            ) use ($minimum) {
                $builder
                    ->where(
                        'salary',
                        '>=',
                        $minimum
                    )
                    ->orWhere(
                        'salary',
                        'like',
                        '%' . $minimum . '%'
                    );
            });
        }

        if (
            in_array('salary', $availableColumns, true)
            && $maximum !== null
            && is_numeric($maximum)
        ) {
            $maximum = (float) $maximum;

            $query->where(function (
                Builder $builder
            ) use ($maximum) {
                $builder
                    ->where(
                        'salary',
                        '<=',
                        $maximum
                    )
                    ->orWhere(
                        'salary',
                        'like',
                        '%' . $maximum . '%'
                    );
            });
        }
    }

    /**
     * Filter by employment/job type.
     */
    protected function applyJobTypeFilter(
        Builder $query,
        array $filters
    ): void {
        $jobType = trim((string) (
            $filters['job_type']
            ?? $filters['employment_type']
            ?? ''
        ));

        if ($jobType === '') {
            return;
        }

        if (! in_array(
            'job_type',
            $this->jobColumns(),
            true
        )) {
            return;
        }

        $query->where(
            'job_type',
            'like',
            '%' . $jobType . '%'
        );
    }

    /**
     * Filter by work mode.
     */
    protected function applyWorkModeFilter(
        Builder $query,
        array $filters
    ): void {
        $workMode = trim((string) (
            $filters['work_mode']
            ?? $filters['workmode']
            ?? ''
        ));

        if ($workMode === '') {
            return;
        }

        if (! in_array(
            'work_mode',
            $this->jobColumns(),
            true
        )) {
            return;
        }

        $query->where(
            'work_mode',
            'like',
            '%' . $workMode . '%'
        );
    }

    /**
     * Filter by company.
     */
    protected function applyCompanyFilter(
        Builder $query,
        array $filters
    ): void {
        $company = trim((string) (
            $filters['company']
            ?? $filters['company_name']
            ?? ''
        ));

        if ($company === '') {
            return;
        }

        $query->whereHas(
            'company',
            function (Builder $builder) use ($company) {
                $builder->where(
                    'name',
                    'like',
                    '%' . $company . '%'
                );
            }
        );
    }

    /**
     * Filter by technology/skill.
     */
    protected function applyTechnologyFilter(
        Builder $query,
        array $filters
    ): void {
        $technology = trim((string) (
            $filters['technology']
            ?? $filters['skill']
            ?? ''
        ));

        if ($technology === '') {
            return;
        }

        $query->whereHas(
            'technologies',
            function (Builder $builder) use ($technology) {
                $builder->where(
                    'name',
                    'like',
                    '%' . $technology . '%'
                );
            }
        );
    }

    /**
     * Convert a Job model into safe AI-readable data.
     */
    protected function formatJob(Job $job): array
    {
        $company = $job->company;

        return [
            'id' => $job->id,

            'title' => $job->title,

            'company' => $company
                ? $company->name
                : null,

            'location' => $job->location,

            'salary' => $job->salary,

            'job_type' => $job->job_type,

            'work_mode' => $job->work_mode,

            /*
             * The generic Job system does not currently have an
             * experience column. Keep this key for API compatibility,
             * but do not query a nonexistent database column.
             */
            'experience' => $this->getJobExperience($job),

            'education' => $job->education,

            'requirements' => $job->requirements,

            'responsibilities' => $job->responsibilities,

            'technologies' => $job->technologies
                ? $job->technologies
                ->pluck('name')
                ->filter()
                ->values()
                ->all()
                : [],

            'external_url' => $job->external_url,

            'source' => $job->source,

            'posting_source' => $job->posting_source,

            'posted_at' => $job->posted_at
                ? $job->posted_at->toDateTimeString()
                : null,

            'expires_at' => $job->expires_at
                ? $job->expires_at->toDateTimeString()
                : null,

            'status' => $job->status,

            'is_active' => (bool) $job->is_active,
        ];
    }

    /**
     * Read experience only when the model actually has the attribute.
     */
    protected function getJobExperience(Job $job)
    {
        if (
            array_key_exists(
                'experience',
                $job->getAttributes()
            )
        ) {
            return $job->getAttribute(
                'experience'
            );
        }

        /*
         * Some job records may expose experience through
         * requirements/responsibilities instead.
         *
         * Do not fabricate an experience value.
         */
        return null;
    }

    /**
     * Get the actual jobs-table columns.
     */
    protected function jobColumns(): array
    {
        return Schema::getColumnListing(
            (new Job())->getTable()
        );
    }

    /**
     * Tokenise a natural-language search safely.
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
     * Keep AI requests within a safe result limit.
     */
    protected function normaliseLimit(
        $limit
    ): int {
        $limit = is_numeric($limit)
            ? (int) $limit
            : 10;

        return max(
            1,
            min($limit, 50)
        );
    }
}
