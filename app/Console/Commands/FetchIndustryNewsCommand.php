<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\NewsSource;
use App\Services\News\IndustryNewsRssService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FetchIndustryNewsCommand extends Command
{
    protected $signature = 'news:fetch-industry
                            {--industry=all : gear, forging, steel or all}
                            {--limit=10 : Maximum articles saved per query}';

    protected $description = 'Fetch and publish current Gear, Forging and Steel industry news';

    public function __construct(private IndustryNewsRssService $rssService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $industry = Str::lower((string) $this->option('industry'));
        $limit = max(1, min(50, (int) $this->option('limit')));
        $feeds = config('industry_news.feeds', []);

        if ($industry !== 'all') {
            if (! isset($feeds[$industry])) {
                $this->error("Invalid industry: {$industry}. Use gear, forging, steel or all.");
                return self::FAILURE;
            }
            $feeds = [$industry => $feeds[$industry]];
        }

        $fetched = $saved = $skipped = $failed = 0;
        foreach ($feeds as $feed) {
            $this->newLine();
            $this->info('Fetching: '.$feed['name']);
            $category = $this->category($feed);
            $source = $this->source($feed['name']);

            foreach ($feed['queries'] as $query) {
                $this->line('  Query: '.$query);
                try {
                    $articles = array_slice($this->rssService->fetch($query), 0, $limit);
                    $fetched += count($articles);
                    foreach ($articles as $article) {
                        $this->saveArticle($article, $category, $source, $feed)
                            ? $saved++
                            : $skipped++;
                    }
                    $source->update(['last_fetched_at' => now(), 'last_fetch_status' => 'success', 'last_error' => null]);
                } catch (\Throwable $exception) {
                    $failed++;
                    $source->update(['last_fetched_at' => now(), 'last_fetch_status' => 'failed', 'last_error' => Str::limit($exception->getMessage(), 1000)]);
                    Log::warning('Industry news query failed', ['query' => $query, 'error' => $exception->getMessage()]);
                    $this->error('  Error: '.$exception->getMessage());
                }
            }
        }

        $this->newLine();
        $this->info("Industry news completed — fetched: {$fetched}, saved: {$saved}, skipped: {$skipped}, failed queries: {$failed}");

        return $failed > 0 && $fetched === 0 ? self::FAILURE : self::SUCCESS;
    }

    private function category(array $feed): NewsCategory
    {
        return NewsCategory::updateOrCreate(
            ['slug' => $feed['category']],
            ['name' => $feed['name'], 'icon' => $feed['icon'] ?? '⚙️', 'color' => '#1769e0', 'is_active' => true]
        );
    }

    private function source(string $name): NewsSource
    {
        return NewsSource::updateOrCreate(
            ['name' => $name.' via Google News'],
            [
                'website' => 'https://news.google.com',
                'source_type' => 'rss',
                'source_category' => 'industry-news',
                'country' => 'India',
                'priority' => 75,
                'trust_tier' => 'aggregator',
                'is_active' => true,
            ]
        );
    }

    private function saveArticle(array $article, NewsCategory $category, NewsSource $source, array $feed): bool
    {
        $normalizedTitle = Str::of($article['title'])->lower()->replaceMatches('/[^a-z0-9]+/i', ' ')->squish()->toString();
        $contentHash = hash('sha256', $normalizedTitle);
        $duplicate = NewsArticle::where('source_url', $article['url'])
            ->orWhere('content_hash', $contentHash)
            ->orWhere(fn ($query) => $query->where('category_id', $category->id)->whereRaw('LOWER(title) = ?', [Str::lower($article['title'])]))
            ->exists();

        if ($duplicate) {
            return false;
        }

        $intelligence = $this->intelligence($article['title'].' '.$article['description'], $feed['name']);
        NewsArticle::create([
            'category_id' => $category->id,
            'source_id' => $source->id,
            'title' => $article['title'],
            'slug' => $this->uniqueSlug($article['title']),
            'excerpt' => Str::limit($article['description'], 220),
            'summary' => Str::limit($article['description'], 1000),
            'career_impact' => $intelligence['career_impact'],
            'industry_impact' => $intelligence['industry_impact'],
            'student_impact' => $intelligence['student_impact'],
            'skills_impact' => 'Demand signals include '.implode(', ', array_slice($intelligence['skills'], 0, 7)).'.',
            'jobs_impact' => 'Potential opportunities span production, quality, maintenance, design, machining and supplier operations.',
            'company_name' => $this->company($normalizedTitle),
            'entities' => array_values(array_filter([$this->company($normalizedTitle), $article['publisher'] ?? null, $feed['name']])),
            'skills' => $intelligence['skills'],
            'recommended_skills' => $intelligence['skills'],
            'locations' => $this->locations($normalizedTitle),
            'industries' => [$feed['name']],
            'relevance_score' => $intelligence['score'],
            'career_impact_level' => $intelligence['score'] >= 75 ? 'high' : 'medium',
            'processing_status' => 'published',
            'content_hash' => $contentHash,
            'is_featured' => false,
            'is_published' => true,
            'published_at' => $article['published_at'],
            'source_published_at' => $article['published_at'],
            'source_url' => $article['url'],
        ]);

        return true;
    }

    private function intelligence(string $text, string $industry): array
    {
        $text = Str::lower($text);
        $skills = [];
        if (str_contains($text, 'gear') || str_contains($text, 'transmission')) $skills = array_merge($skills, ['Gear Design', 'Gear Hobbing', 'Gear Grinding', 'CNC Machining', 'CAD/CAM', 'Metrology', 'Heat Treatment']);
        if (str_contains($text, 'forging') || str_contains($text, 'forged')) $skills = array_merge($skills, ['Forging Process', 'Die Design', 'Metallurgy', 'Heat Treatment', 'CNC Machining', 'GD&T', 'Quality Control']);
        if (str_contains($text, 'steel') || str_contains($text, 'stainless') || str_contains($text, 'metallurgy')) $skills = array_merge($skills, ['Metallurgy', 'Steel Processing', 'Materials Engineering', 'Quality Engineering', 'Process Engineering', 'Automation']);
        $skills = array_values(array_unique($skills ?: ['Mechanical Engineering', 'Manufacturing', 'Quality Control']));
        $score = 50;
        foreach (['investment' => 10, 'plant' => 8, 'manufacturing' => 7, 'capacity' => 7, 'jobs' => 8, 'hiring' => 8, 'automation' => 6, 'forging' => 6, 'gear' => 6, 'steel' => 6, 'technology' => 5] as $word => $points) if (str_contains($text, $word)) $score += $points;

        return [
            'skills' => $skills,
            'score' => min($score, 100),
            'career_impact' => "Developments in {$industry} can increase demand for mechanical, production, quality and process-engineering expertise.",
            'industry_impact' => "This development may affect investment, capacity, technology adoption and supply chains across {$industry}.",
            'student_impact' => 'Students should combine mechanical fundamentals with CAD/CAM, automation, quality and practical manufacturing experience.',
        ];
    }

    private function company(string $text): ?string
    {
        foreach (['mahindra' => 'Mahindra', 'tata' => 'Tata', 'jindal' => 'Jindal', 'jsw' => 'JSW', 'sail' => 'SAIL', 'arcelormittal' => 'ArcelorMittal', 'vardhman' => 'Vardhman', 'aichi steel' => 'Aichi Steel', 'balu forge' => 'Balu Forge', 'tafe' => 'TAFE', 'deutz' => 'DEUTZ', 'bharat forge' => 'Bharat Forge', 'bharat gears' => 'Bharat Gears'] as $needle => $company) if (str_contains($text, $needle)) return $company;
        return null;
    }

    private function locations(string $text): array
    {
        $found = [];
        foreach (['punjab', 'ludhiana', 'hoshiarpur', 'chandigarh', 'delhi', 'gurugram', 'pune', 'mumbai', 'nagpur', 'alwar', 'rajasthan', 'maharashtra', 'gujarat', 'chennai', 'bengaluru', 'bangalore', 'hyderabad', 'jamshedpur', 'bhilai', 'odisha'] as $location) if (str_contains($text, $location)) $found[] = Str::title($location);
        return array_values(array_unique($found));
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'industry-news';
        $slug = $base;
        for ($suffix = 2; NewsArticle::where('slug', $slug)->exists(); $suffix++) $slug = $base.'-'.$suffix;
        return $slug;
    }
}
