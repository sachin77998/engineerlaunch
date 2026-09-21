<?php

namespace App\AI\Tools;

use App\Models\IndustrialArea;
use Illuminate\Database\Eloquent\Builder;

class SearchIndustrialAreasTool
{
    /**
     * Search the existing industrial-area directory.
     *
     * This tool only returns records that already exist in the platform.
     * It never creates or invents industrial areas.
     */
    public function execute(array $filters = []): array
    {
        $query = IndustrialArea::query();

        /*
         * Use the existing visibility scope when available.
         */
        if (method_exists(IndustrialArea::class, 'scopeVisible')) {
            $query->visible();
        } else {
            $query->where('is_active', true);
        }

        $query->with('state');

        $this->applySearchFilter($query, $filters);
        $this->applyStateFilter($query, $filters);
        $this->applyLocationFilter($query, $filters);
        $this->applyAreaTypeFilter($query, $filters);
        $this->applySectorFilter($query, $filters);

        $limit = $this->normaliseLimit(
            $filters['limit'] ?? 20
        );

        $areas = $query
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'count' => $areas->count(),
            'filters' => $filters,
            'industrial_areas' => $areas
                ->map(function (IndustrialArea $area) {
                    return $this->formatArea($area);
                })
                ->values()
                ->all(),
        ];
    }

    /**
     * Search by area name, city, district, description and sectors.
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
                    ->orWhere('city', 'like', $like)
                    ->orWhere('district', 'like', $like)
                    ->orWhere('pincode', 'like', $like)
                    ->orWhere('area_type', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('sectors', 'like', $like);

                $builder->orWhereHas(
                    'state',
                    function (Builder $state) use ($like) {
                        $state
                            ->where('name', 'like', $like)
                            ->orWhere('code', 'like', $like);
                    }
                );
            }
        });
    }

    /**
     * Filter by state.
     */
    protected function applyStateFilter(
        Builder $query,
        array $filters
    ): void {
        $state = trim((string) (
            $filters['state']
            ?? $filters['state_name']
            ?? ''
        ));

        $stateId = $filters['state_id'] ?? null;

        if (
            $stateId !== null &&
            is_numeric($stateId)
        ) {
            $query->where(
                'state_id',
                (int) $stateId
            );

            return;
        }

        if ($state === '') {
            return;
        }

        $query->whereHas(
            'state',
            function (Builder $builder) use ($state) {
                $builder->where(
                    'name',
                    'like',
                    '%' . $state . '%'
                )->orWhere(
                    'slug',
                    'like',
                    '%' . strtolower($state) . '%'
                )->orWhere(
                    'code',
                    'like',
                    '%' . strtoupper($state) . '%'
                );
            }
        );
    }

    /**
     * Filter by city or district.
     */
    protected function applyLocationFilter(
        Builder $query,
        array $filters
    ): void {
        $location = trim((string) (
            $filters['location']
            ?? $filters['city']
            ?? $filters['district']
            ?? ''
        ));

        if ($location === '') {
            return;
        }

        if (
            isset($filters['city']) &&
            trim((string) $filters['city']) !== ''
        ) {
            $query->where(
                'city',
                'like',
                '%' . trim((string) $filters['city']) . '%'
            );
        }

        if (
            isset($filters['district']) &&
            trim((string) $filters['district']) !== ''
        ) {
            $query->where(
                'district',
                'like',
                '%' . trim((string) $filters['district']) . '%'
            );
        }

        /*
         * If only location was supplied, search both city and district.
         */
        if (
            ! isset($filters['city']) &&
            ! isset($filters['district'])
        ) {
            $query->where(function (Builder $builder) use ($location) {
                $like = '%' . $location . '%';

                $builder
                    ->where('city', 'like', $like)
                    ->orWhere('district', 'like', $like)
                    ->orWhere('name', 'like', $like);
            });
        }
    }

    /**
     * Filter by industrial-area type.
     */
    protected function applyAreaTypeFilter(
        Builder $query,
        array $filters
    ): void {
        $areaType = trim((string) (
            $filters['area_type']
            ?? $filters['type']
            ?? ''
        ));

        if ($areaType === '') {
            return;
        }

        $query->where(
            'area_type',
            'like',
            '%' . $areaType . '%'
        );
    }

    /**
     * Filter by sector.
     *
     * The existing industrial_areas table stores sectors as JSON,
     * therefore this works through JSON text matching rather than assuming
     * a separate area-sector table.
     */
    protected function applySectorFilter(
        Builder $query,
        array $filters
    ): void {
        $sector = trim((string) (
            $filters['sector']
            ?? $filters['industry']
            ?? ''
        ));

        if ($sector === '') {
            return;
        }

        $query->where(
            'sectors',
            'like',
            '%' . $sector . '%'
        );
    }

    /**
     * Format an industrial area for the AI layer.
     */
    protected function formatArea(
        IndustrialArea $area
    ): array {
        return [
            'id' => $area->id,

            'name' => $area->name,

            'slug' => $area->slug,

            'state_id' => $area->state_id,

            'state' => $area->state
                ? $area->state->name
                : null,

            'state_code' => $area->state
                ? $area->state->code
                : null,

            'district' => $area->district,

            'city' => $area->city,

            'pincode' => $area->pincode,

            'area_type' => $area->area_type,

            'sectors' => $this->normaliseSectors(
                $area->sectors
            ),

            'description' => $area->description,

            'latitude' => $area->latitude,

            'longitude' => $area->longitude,

            'is_featured' => (bool) $area->is_featured,

            'is_active' => (bool) $area->is_active,

            'verification_status' => $area->verification_status,

            'source_name' => $area->source_name,

            'source_url' => $area->source_url,

            'last_verified_at' => $area->last_verified_at
                ? $area->last_verified_at->toDateTimeString()
                : null,
        ];
    }

    /**
     * Normalize sectors regardless of whether the model cast has already
     * converted the JSON field into an array.
     */
    protected function normaliseSectors($sectors): array
    {
        if (is_array($sectors)) {
            return array_values(
                array_filter(
                    $sectors,
                    function ($sector) {
                        return is_string($sector)
                            && trim($sector) !== '';
                    }
                )
            );
        }

        if (
            is_string($sectors) &&
            trim($sectors) !== ''
        ) {
            $decoded = json_decode(
                $sectors,
                true
            );

            if (is_array($decoded)) {
                return array_values(
                    array_filter(
                        $decoded,
                        function ($sector) {
                            return is_string($sector)
                                && trim($sector) !== '';
                        }
                    )
                );
            }

            return [$sectors];
        }

        return [];
    }

    /**
     * Tokenise natural-language search text.
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
     * Keep AI result sets bounded.
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
