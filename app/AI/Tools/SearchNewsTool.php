<?php

namespace App\AI\Tools;

use App\Models\NewsArticle;
use Illuminate\Database\Eloquent\Builder;

class SearchNewsTool
{
    /**
     * Search published news already available on the platform.
     *
     * This tool only returns existing database records.
     * It never invents news, companies, events or source URLs.
     */
    public function execute(array $filters = []): array
    {
        $query = NewsArticle::query();

        $this->applyPublishedFilter($query);
        $this->applySearchFilter($query, $filters);
        $this->applyCategoryFilter($query, $filters);
        $this->applyIndustryFilter($query, $filters);
        $this->applyCompanyFilter($query, $filters);
        $this->applyDateFilter($query, $filters);

        $limit = $this->normaliseLimit(
            $filters['limit'] ?? 20
        );

        $articles = $query
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        return [
            'success' => true,
            'count' => $articles->count(),
            'filters' => $filters,
            'articles' => $articles
                ->map(function (NewsArticle $article) {
                    return $this->formatArticle($article);
                })
                ->values()
                ->all(),
        ];
    }

    /**
     * Only expose published articles.
     */
    protected function applyPublishedFilter(
        Builder $query
    ): void {
        /*
         * Your news schema uses is_published.
         */
        $query->where('is_published', true);

        /*
         * Do not expose future-dated articles.
         */
        $query->where(function (Builder $builder) {
            $builder
                ->whereNull('published_at')
                ->orWhere(
                    'published_at',
                    '<=',
                    now()
                );
        });
    }

    /**
     * Search title, summary, impact fields, companies, technologies,
     * industries, skills and source information.
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
                    ->orWhere(
                        'title',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'excerpt',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'summary',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'career_impact',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'industry_impact',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'student_impact',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'skills_impact',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'jobs_impact',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'company_name',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'industry',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'source_author',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'what_happened',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'why_it_matters',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'recommended_skills',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'companies',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'technologies',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'industries',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'skills',
                        'like',
                        $like
                    );
            }
        });
    }

    /**
     * Filter by category.
     *
     * Supports category ID and category name/slug.
     */
    protected function applyCategoryFilter(
        Builder $query,
        array $filters
    ): void {
        $categoryId = $filters['category_id'] ?? null;

        if (
            $categoryId !== null &&
            is_numeric($categoryId)
        ) {
            $query->where(
                'category_id',
                (int) $categoryId
            );

            return;
        }

        $category = trim((string) (
            $filters['category']
            ?? $filters['category_slug']
            ?? ''
        ));

        if ($category === '') {
            return;
        }

        $query->whereHas(
            'category',
            function (Builder $builder) use ($category) {
                $like = '%' . $category . '%';

                $builder
                    ->where(
                        'name',
                        'like',
                        $like
                    )
                    ->orWhere(
                        'slug',
                        'like',
                        $like
                    );
            }
        );
    }

    /**
     * Filter by industry.
     */
    protected function applyIndustryFilter(
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

        $query->where(
            'industry',
            'like',
            '%' . $industry . '%'
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

        $query->where(function (Builder $builder) use ($company) {
            $like = '%' . $company . '%';

            $builder
                ->where(
                    'company_name',
                    'like',
                    $like
                )
                ->orWhere(
                    'companies',
                    'like',
                    $like
                );
        });
    }

    /**
     * Optional date filtering.
     */
    protected function applyDateFilter(
        Builder $query,
        array $filters
    ): void {
        $from = $filters['from_date'] ?? null;
        $to = $filters['to_date'] ?? null;

        if ($from) {
            $query->whereDate(
                'published_at',
                '>=',
                $from
            );
        }

        if ($to) {
            $query->whereDate(
                'published_at',
                '<=',
                $to
            );
        }
    }

    /**
     * Convert an article into a safe AI response structure.
     */
    protected function formatArticle(
        NewsArticle $article
    ): array {
        return [
            'id' => $article->id,

            'title' => $article->title,

            'slug' => $article->slug,

            'excerpt' => $article->excerpt,

            'summary' => $article->summary,

            'what_happened' => $article->what_happened,

            'why_it_matters' => $article->why_it_matters,

            'career_impact' => $article->career_impact,

            'industry_impact' => $article->industry_impact,

            'student_impact' => $article->student_impact,

            'skills_impact' => $article->skills_impact,

            'jobs_impact' => $article->jobs_impact,

            'company_name' => $article->company_name,

            'industry' => $article->industry,

            'companies' => $this->normaliseList(
                $article->companies
            ),

            'technologies' => $this->normaliseList(
                $article->technologies
            ),

            'industries' => $this->normaliseList(
                $article->industries
            ),

            'skills' => $this->normaliseList(
                $article->skills
            ),

            'recommended_skills' => $this->normaliseList(
                $article->recommended_skills
            ),

            'locations' => $this->normaliseList(
                $article->locations
            ),

            'entities' => $this->normaliseList(
                $article->entities
            ),

            'career_signal' => $article->career_signal,

            'market_signal' => $article->market_signal,

            'intelligence_score' => $article->intelligence_score,

            'source_author' => $article->source_author,

            'source_url' => $article->source_url,

            'source_published_at' => $article->source_published_at
                ? $article->source_published_at->toDateTimeString()
                : null,

            'published_at' => $article->published_at
                ? $article->published_at->toDateTimeString()
                : null,

            'is_featured' => (bool) $article->is_featured,
        ];
    }

    /**
     * Normalize JSON/text fields.
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
     * Tokenise natural-language news queries.
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
            'news',
            'latest',
            'article',
            'articles',
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
            : 20;

        return max(
            1,
            min($limit, 50)
        );
    }
}
