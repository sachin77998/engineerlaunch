<?php

namespace App\AI\Tools;

use App\Models\Company;
use App\Models\IndustrialCompany;
use Illuminate\Database\Eloquent\Builder;

class SearchCompaniesTool
{
    /**
     * Search companies from the platform databases.
     *
     * This tool only returns existing company records.
     * It never creates or invents companies.
     */
    public function execute(array $filters = []): array
    {
        $source = strtolower(
            trim((string) ($filters['source'] ?? 'all'))
        );

        $limit = $this->normaliseLimit(
            $filters['limit'] ?? 10
        );

        $results = [];

        if ($source !== 'industrial') {
            $results['companies'] = $this->searchGenericCompanies(
                $filters,
                $limit
            );
        }

        if ($source !== 'generic') {
            $results['industrial_companies'] = $this->searchIndustrialCompanies(
                $filters,
                $limit
            );
        }

        return [
            'success' => true,
            'count' => count($results['companies'] ?? [])
                + count($results['industrial_companies'] ?? []),
            'filters' => $filters,
            'companies' => $results['companies'] ?? [],
            'industrial_companies' => $results['industrial_companies'] ?? [],
        ];
    }

    /**
     * Search the existing generic companies table.
     */
    protected function searchGenericCompanies(
        array $filters,
        int $limit
    ): array {
        $query = Company::query();

        if (method_exists(Company::class, 'scopeActive')) {
            $query->active();
        } elseif ($this->hasColumn('companies', 'is_active')) {
            $query->where('is_active', true);
        }

        $this->applyGenericKeywordFilter(
            $query,
            $filters
        );

        $this->applyGenericIndustryFilter(
            $query,
            $filters
        );

        $this->applyGenericCountryFilter(
            $query,
            $filters
        );

        return $query
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (Company $company) {
                return $this->formatGenericCompany($company);
            })
            ->values()
            ->all();
    }

    /**
     * Search the industrial company directory.
     */
    protected function searchIndustrialCompanies(
        array $filters,
        int $limit
    ): array {
        $query = IndustrialCompany::query();

        if (method_exists(IndustrialCompany::class, 'scopeVisible')) {
            $query->visible();
        } else {
            if ($this->hasColumn('industrial_companies', 'is_active')) {
                $query->where('is_active', true);
            }
        }

        $query->with([
            'industrialArea',
        ]);

        $this->applyIndustrialKeywordFilter(
            $query,
            $filters
        );

        $this->applyIndustrialIndustryFilter(
            $query,
            $filters
        );

        $this->applyIndustrialAreaFilter(
            $query,
            $filters
        );

        return $query
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function (IndustrialCompany $company) {
                return $this->formatIndustrialCompany($company);
            })
            ->values()
            ->all();
    }

    /**
     * Generic company keyword search.
     */
    protected function applyGenericKeywordFilter(
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

        $query->where(function (Builder $builder) use ($terms) {
            foreach ($terms as $term) {
                $like = '%' . $term . '%';

                $builder
                    ->orWhere('name', 'like', $like)
                    ->orWhere('industry', 'like', $like)
                    ->orWhere('sector', 'like', $like)
                    ->orWhere('description', 'like', $like);
            }
        });
    }

    /**
     * Generic company industry filter.
     */
    protected function applyGenericIndustryFilter(
        Builder $query,
        array $filters
    ): void {
        $industry = trim((string) (
            $filters['industry']
            ?? $filters['sector']
            ?? ''
        ));

        if ($industry === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($industry) {
            $like = '%' . $industry . '%';

            $builder
                ->where('industry', 'like', $like)
                ->orWhere('sector', 'like', $like);
        });
    }

    /**
     * Generic company country filter.
     */
    protected function applyGenericCountryFilter(
        Builder $query,
        array $filters
    ): void {
        $country = trim((string) (
            $filters['country'] ?? ''
        ));

        if ($country === '') {
            return;
        }

        if ($this->hasColumn('companies', 'country')) {
            $query->where(
                'country',
                'like',
                '%' . $country . '%'
            );
        }
    }

    /**
     * Industrial company keyword search.
     */
    protected function applyIndustrialKeywordFilter(
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

        $query->where(function (Builder $builder) use ($terms) {
            foreach ($terms as $term) {
                $like = '%' . $term . '%';

                $builder
                    ->orWhere('name', 'like', $like)
                    ->orWhere('industry', 'like', $like)
                    ->orWhere('sector', 'like', $like)
                    ->orWhere('plant_name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('facility_type', 'like', $like);

                $builder->orWhereHas(
                    'industrialArea',
                    function (Builder $area) use ($like) {
                        $area
                            ->where('name', 'like', $like)
                            ->orWhere('city', 'like', $like)
                            ->orWhere('district', 'like', $like);
                    }
                );
            }
        });
    }

    /**
     * Industrial company industry/sector filter.
     */
    protected function applyIndustrialIndustryFilter(
        Builder $query,
        array $filters
    ): void {
        $industry = trim((string) (
            $filters['industry']
            ?? $filters['sector']
            ?? ''
        ));

        if ($industry === '') {
            return;
        }

        $query->where(function (Builder $builder) use ($industry) {
            $like = '%' . $industry . '%';

            $builder
                ->where('industry', 'like', $like)
                ->orWhere('sector', 'like', $like);
        });
    }

    /**
     * Filter industrial companies by industrial area/city/district.
     */
    protected function applyIndustrialAreaFilter(
        Builder $query,
        array $filters
    ): void {
        $location = trim((string) (
            $filters['location']
            ?? $filters['area']
            ?? $filters['city']
            ?? $filters['district']
            ?? ''
        ));

        if ($location === '') {
            return;
        }

        $query->whereHas(
            'industrialArea',
            function (Builder $area) use ($location) {
                $like = '%' . $location . '%';

                $area
                    ->where('name', 'like', $like)
                    ->orWhere('city', 'like', $like)
                    ->orWhere('district', 'like', $like);
            }
        );
    }

    /**
     * Format a generic company for the AI layer.
     */
    protected function formatGenericCompany(
        Company $company
    ): array {
        return [
            'id' => $company->id,
            'type' => 'company',
            'name' => $company->name,
            'slug' => $company->slug,
            'industry' => $company->industry,
            'sector' => $company->sector,
            'country' => $company->country,
            'company_type' => $company->company_type,
            'employee_count' => $company->employee_count,
            'website' => $company->website,
            'careers_url' => $company->careers_url,
            'ats_provider' => $company->ats_provider,
            'jobs_feed_url' => $company->jobs_feed_url,
        ];
    }

    /**
     * Format an industrial company for the AI layer.
     */
    protected function formatIndustrialCompany(
        IndustrialCompany $company
    ): array {
        $area = $company->industrialArea;

        return [
            'id' => $company->id,
            'type' => 'industrial_company',
            'name' => $company->name,
            'slug' => $company->slug,
            'industry' => $company->industry,
            'sector' => $company->sector,
            'plant_name' => $company->plant_name,
            'facility_type' => $company->facility_type,
            'website' => $company->website,
            'careers_url' => $company->careers_url,
            'description' => $company->description,

            'industrial_area' => $area
                ? $area->name
                : null,

            'city' => $area
                ? $area->city
                : null,

            'district' => $area
                ? $area->district
                : null,

            'state' => $area && $area->state
                ? $area->state->name
                : null,

            'is_verified' => (bool) $company->is_verified,

            'verification_status' => $company->verification_status,

            'source_name' => $company->source_name,

            'source_url' => $company->source_url,

            'last_verified_at' => $company->last_verified_at
                ? $company->last_verified_at->toDateTimeString()
                : null,
        ];
    }

    /**
     * Tokenise search text.
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
                    && ! in_array($token, $stopWords, true);
            }
        );

        return array_values(
            array_unique(
                array_slice($tokens, 0, 12)
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
            : 10;

        return max(
            1,
            min($limit, 50)
        );
    }

    /**
     * Check whether a database column exists.
     */
    protected function hasColumn(
        string $table,
        string $column
    ): bool {
        try {
            return \Schema::hasColumn(
                $table,
                $column
            );
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
