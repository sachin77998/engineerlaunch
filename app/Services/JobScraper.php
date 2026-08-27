<?php

namespace App\Services;

use App\Models\Job;
use App\Models\Company;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Support\DiscoveryCache;

class JobScraper
{
    protected Client $httpClient;
    protected string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
    protected $technologies;
    protected $categories;

    public function __construct()
    {
        $this->httpClient = new Client([
            'headers' => [
                'User-Agent' => $this->userAgent,
            ],
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => true,
        ]);
    }

    /**
     * Scrape jobs for a specific company
     */
    public function scrapeCompany(Company $company): array
    {
        $results = [
            'success' => false,
            'company' => $company->name,
            'jobs_found' => 0,
            'jobs_added' => 0,
            'jobs_updated' => 0,
            'errors' => [],
            'exception' => null,
        ];

        try {
            // Each company requires its own scraper logic
            // This is a template - implement company-specific logic
            $jobs = $this->scrapeCompanyJobs($company);
            if ($jobs === []) {
                throw new \RuntimeException('The feed returned no recognizable jobs; existing listings were preserved.');
            }

            foreach ($jobs as $jobData) {
                try {
                    $job = $this->createOrUpdateJob($company, $jobData);
                    if ($job->wasRecentlyCreated) {
                        $results['jobs_added']++;
                    } else {
                        $results['jobs_updated']++;
                    }
                    $results['jobs_found']++;
                } catch (\Exception $e) {
                    $results['errors'][] = "Error processing job: " . $e->getMessage();
                }
            }

            // Never retire an entire company's catalogue because an upstream
            // page temporarily returned an empty response or changed markup.
            if ($company->sync_enabled && count($jobs) > 0) {
                $currentUrls = collect($jobs)->pluck('external_url')->filter()->all();
                $company->jobs()->whereNotIn('external_url', $currentUrls ?: [''])->update(['is_active' => false]);
                $company->forceFill(['last_synced_at' => now()])->save();
            }

            $results['success'] = true;
            DiscoveryCache::invalidate();
        } catch (\Exception $e) {
            $results['errors'][] = "Scraping failed: " . $e->getMessage();
            $results['exception'] = $e;
        }

        return $results;
    }

    /**
     * Scrape jobs from company careers page (override in child classes)
     */
    protected function scrapeCompanyJobs(Company $company): array
    {
        return match ($company->ats_provider) {
            'greenhouse' => $this->scrapeGreenhouse($company),
            'lever' => $this->scrapeLever($company),
            'successfactors' => $this->scrapeSuccessFactors($company),
            'workday' => $this->scrapeWorkday($company),
            'smartrecruiters' => $this->scrapeSmartRecruiters($company),
            'amazon' => $this->scrapeAmazon($company),
            'icims_jibe' => $this->scrapeIcimsJibe($company),
            default => throw new \RuntimeException('No supported ATS feed is configured.'),
        };
    }

    protected function scrapeIcimsJibe(Company $company): array
    {
        $endpoint = $company->jobs_feed_url;
        if (!$endpoint) throw new \RuntimeException('An iCIMS/Jibe jobs endpoint is required.');
        $separator = str_contains($endpoint, '?') ? '&' : '?';
        $limit = 100;
        $makeUrl = fn (int $page) => $endpoint.$separator.http_build_query(['limit' => $limit, 'page' => $page]);
        $first = json_decode($this->getContent($makeUrl(1)), true, 512, JSON_THROW_ON_ERROR);
        $total = min((int) ($first['totalCount'] ?? count($first['jobs'] ?? [])), 10000);
        $pages = [1 => $first['jobs'] ?? []];
        $pageCount = (int) ceil($total / $limit);
        $requests = function () use ($makeUrl, $pageCount) {
            for ($page = 2; $page <= $pageCount; $page++) {
                yield $page => new Request('GET', $makeUrl($page), [
                    'Accept' => 'application/json', 'User-Agent' => $this->userAgent,
                ]);
            }
        };
        $pool = new Pool($this->httpClient, $requests(), [
            'concurrency' => 6,
            'fulfilled' => function ($response, int $page) use (&$pages) {
                $payload = json_decode((string) $response->getBody(), true);
                $pages[$page] = $payload['jobs'] ?? [];
            },
            'rejected' => fn ($reason, int $page) => Log::warning("iCIMS/Jibe page failed at page {$page}: {$reason}"),
        ]);
        $pool->promise()->wait();
        ksort($pages);
        $items = array_merge(...array_values($pages));

        return collect($items)->map(function (array $item) use ($company) {
            $data = $item['data'] ?? $item;
            $externalUrl = $data['apply_url'] ?? null;
            if (!$externalUrl && !empty($data['slug'])) {
                $externalUrl = rtrim($company->careers_url, '/').'/jobs/'.$data['slug'];
            }
            $location = $data['full_location'] ?? $data['location_name'] ?? implode(', ', array_filter([
                $data['city'] ?? null, $data['state'] ?? null, $data['country'] ?? null,
            ]));

            return $this->normalizeJob($company, [
                'title' => $data['title'] ?? 'Untitled',
                'description' => strip_tags(implode(' ', array_filter([
                    $data['description'] ?? null, $data['responsibilities'] ?? null,
                    $data['qualifications'] ?? null,
                ]))),
                'location' => $location ?: 'Not specified',
                'job_type' => $data['employment_type'] ?? 'Full-time',
                'external_url' => $externalUrl,
                'posted_at' => $data['posted_date'] ?? $data['create_date'] ?? now(),
                'posting_source' => 'official_company',
            ]);
        })->filter(fn ($job) => !empty($job['external_url']))->values()->all();
    }

    /**
     * Amazon publishes a first-party JSON search feed on amazon.jobs. Keep this
     * separate from generic page scraping so changes fail safely and never
     * manufacture a zero-job catalogue.
     */
    protected function scrapeAmazon(Company $company): array
    {
        $endpoint = rtrim($company->jobs_feed_url ?: 'https://www.amazon.jobs/en/search.json', '?');
        $jobs = [];
        $limit = 100;

        $makeUrl = fn (int $offset) => $endpoint.'?'.http_build_query([
                'offset' => $offset,
                'result_limit' => $limit,
                'sort' => 'recent',
            ]);
        $first = json_decode($this->getContent($makeUrl(0)), true, 512, JSON_THROW_ON_ERROR);
        $total = min((int) ($first['hits'] ?? 0), 10000);
        $pages = [0 => $first['jobs'] ?? []];
        $requests = function () use ($makeUrl, $limit, $total) {
            for ($offset = $limit; $offset < $total; $offset += $limit) {
                yield $offset => new Request('GET', $makeUrl($offset), [
                    'Accept' => 'application/json', 'User-Agent' => $this->userAgent,
                ]);
            }
        };
        $pool = new Pool($this->httpClient, $requests(), [
            'concurrency' => 8,
            'fulfilled' => function ($response, int $offset) use (&$pages) {
                $payload = json_decode((string) $response->getBody(), true);
                $pages[$offset] = $payload['jobs'] ?? [];
            },
            'rejected' => fn ($reason, int $offset) => Log::warning("Amazon jobs page failed at offset {$offset}: {$reason}"),
        ]);
        $pool->promise()->wait();
        ksort($pages);

        foreach (array_merge(...array_values($pages)) as $item) {

                $path = $item['job_path'] ?? null;
                if (!$path) continue;
                $externalUrl = 'https://www.amazon.jobs'.($path[0] === '/' ? $path : '/'.$path);
                $description = implode(' ', array_filter([
                    $item['description_short'] ?? null,
                    $item['description'] ?? null,
                    $item['basic_qualifications'] ?? null,
                    $item['preferred_qualifications'] ?? null,
                ]));

                $jobs[$externalUrl] = $this->normalizeJob($company, [
                    'title' => $item['title'] ?? 'Untitled',
                    'description' => strip_tags($description),
                    'location' => $item['location'] ?? 'Not specified',
                    'job_type' => $item['job_schedule_type'] ?? 'Full-time',
                    'external_url' => $externalUrl,
                    'posted_at' => $item['posted_date'] ?? now(),
                    'posting_source' => 'official_company',
                ]);
        }

        return array_values($jobs);
    }

    protected function scrapeSmartRecruiters(Company $company): array
    {
        $identifier = $company->ats_identifier;
        if (!$identifier) throw new \RuntimeException('A SmartRecruiters company identifier is required.');
        $endpoint = $company->jobs_feed_url ?: "https://api.smartrecruiters.com/v1/companies/{$identifier}/postings";
        $jobs = [];
        for ($offset = 0; $offset < 10000; $offset += 100) {
            $payload = json_decode($this->getContent($endpoint.'?'.http_build_query(['limit' => 100, 'offset' => $offset])), true, 512, JSON_THROW_ON_ERROR);
            $items = $payload['content'] ?? [];
            foreach ($items as $item) {
                $place = $item['location'] ?? [];
                $externalUrl = "https://jobs.smartrecruiters.com/{$identifier}/".($item['id'] ?? '');
                $jobs[$externalUrl] = $this->normalizeJob($company, [
                    'title' => $item['name'] ?? 'Untitled',
                    'description' => implode(' ', array_filter([$item['department']['label'] ?? null, $item['function']['label'] ?? null, $item['industry']['label'] ?? null])),
                    'location' => $place['fullLocation'] ?? $place['city'] ?? 'Not specified',
                    'job_type' => $item['typeOfEmployment']['label'] ?? 'Full-time',
                    'experience_level' => $item['experienceLevel']['label'] ?? null,
                    'work_mode' => !empty($place['remote']) ? 'remote' : (!empty($place['hybrid']) ? 'hybrid' : 'office'),
                    'external_url' => $externalUrl,
                    'posted_at' => $item['releasedDate'] ?? now(),
                    'posting_source' => 'official_company',
                ]);
            }
            if (count($items) < 100 || $offset + 100 >= (int) ($payload['totalFound'] ?? 0)) break;
        }
        return array_values($jobs);
    }

    protected function scrapeWorkday(Company $company): array
    {
        $endpoint = $company->jobs_feed_url;
        if (!$endpoint) throw new \RuntimeException('A Workday jobs endpoint is required.');

        $makeBody = fn (int $offset) => json_encode([
            'appliedFacets' => (object) [],
            'limit' => 20,
            'offset' => $offset,
            'searchText' => '',
        ], JSON_THROW_ON_ERROR);

        $first = json_decode($this->postJson($endpoint, $makeBody(0)), true, 512, JSON_THROW_ON_ERROR);
        $total = min((int) ($first['total'] ?? 0), 10000);
        $pages = [0 => $first['jobPostings'] ?? []];

        $requests = function () use ($endpoint, $makeBody, $total) {
            for ($offset = 20; $offset < $total; $offset += 20) {
                yield $offset => new Request('POST', $endpoint, [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => $this->userAgent,
                ], $makeBody($offset));
            }
        };

        $pool = new Pool($this->httpClient, $requests(), [
            'concurrency' => 8,
            'fulfilled' => function ($response, int $offset) use (&$pages) {
                $payload = json_decode((string) $response->getBody(), true);
                $pages[$offset] = $payload['jobPostings'] ?? [];
            },
            'rejected' => function ($reason, int $offset) {
                Log::warning("Workday page failed at offset {$offset}: {$reason}");
            },
        ]);
        $pool->promise()->wait();
        ksort($pages);

        $urlParts = parse_url($endpoint);
        $path = $urlParts['path'] ?? '';
        preg_match('~/wday/cxs/[^/]+/([^/]+)/jobs~', $path, $matches);
        $site = $matches[1] ?? '';
        $baseUrl = ($urlParts['scheme'] ?? 'https').'://'.($urlParts['host'] ?? '').'/'.$site;
        $jobs = [];

        foreach (array_merge(...array_values($pages)) as $item) {
            $externalPath = $item['externalPath'] ?? null;
            if (!$externalPath) continue;
            $location = $item['locationsText'] ?? collect($item['bulletFields'] ?? [])->last() ?? 'Not specified';
            $externalUrl = rtrim($baseUrl, '/').'/'.ltrim($externalPath, '/');
            $jobs[$externalUrl] = $this->normalizeJob($company, [
                'title' => $item['title'] ?? 'Untitled',
                'description' => implode(' ', $item['bulletFields'] ?? []),
                'location' => $location,
                'external_url' => $externalUrl,
                'posted_at' => $this->workdayPostedAt($item['postedOn'] ?? ''),
                'posting_source' => 'official_company',
            ]);
        }

        return array_values($jobs);
    }

    protected function workdayPostedAt(string $value): Carbon
    {
        if (preg_match('/(\d+)\+?\s+Days?\s+Ago/i', $value, $match)) return now()->subDays((int) $match[1]);
        if (stripos($value, 'Yesterday') !== false) return now()->subDay();
        return now();
    }

    protected function scrapeSuccessFactors(Company $company): array
    {
        $baseUrl = rtrim($company->jobs_feed_url ?: $company->careers_url, '/');
        $jobs = [];

        // SuccessFactors career sites expose 50 jobs per page using startrow.
        // A hard ceiling prevents a broken pagination response looping forever.
        for ($start = 0; $start < 10000; $start += 50) {
            $separator = str_contains($baseUrl, '?') ? '&' : '?';
            $url = $baseUrl.$separator.'startrow='.$start;
            $document = new \DOMDocument();
            @$document->loadHTML($this->getContent($url), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($document);
            $pageJobs = [];

            $links = $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " jobTitle-link ") or @data-careersite-propertyid="title" or contains(@href, "/job/")]');
            foreach ($links as $link) {
                    $title = trim($link->textContent);
                    $href = trim((string) $link->getAttribute('href'));
                    if ($title === '' || $href === '') continue;

                    $row = $link;
                    while ($row && strtolower($row->nodeName) !== 'tr') $row = $row->parentNode;
                    $rowText = trim(preg_replace('/\s+/', ' ', $row?->textContent ?? ''));
                    $location = 'Not specified';
                    if ($row) {
                        $locationNodes = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " jobLocation ") or @data-careersite-propertyid="location"]', $row);
                        if ($locationNodes->length) $location = trim($locationNodes->item(0)->textContent);
                    }

                    $absoluteUrl = str_starts_with($href, 'http')
                        ? $href
                        : rtrim((string) parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST), '/').'/'.ltrim($href, '/');

                    $pageJobs[$absoluteUrl] = $this->normalizeJob($company, [
                        'title' => $title,
                        'description' => $rowText,
                        'location' => $location,
                        'external_url' => $absoluteUrl,
                        'posted_at' => now(),
                    ]);
            }

            if ($pageJobs === []) break;
            $before = count($jobs);
            $jobs += $pageJobs;
            if (count($jobs) === $before || count($pageJobs) < 50) break;
        }

        return array_values($jobs);
    }

    protected function scrapeGreenhouse(Company $company): array
    {
        $url = $company->jobs_feed_url ?: "https://boards-api.greenhouse.io/v1/boards/{$company->ats_identifier}/jobs?content=true";
        $payload = json_decode($this->getContent($url), true, 512, JSON_THROW_ON_ERROR);

        return collect($payload['jobs'] ?? [])->map(function (array $item) use ($company) {
            $location = $item['location']['name'] ?? 'Not specified';
            $description = html_entity_decode(strip_tags($item['content'] ?? ''));
            return $this->normalizeJob($company, [
                'title' => $item['title'] ?? 'Untitled', 'description' => $description,
                'location' => $location, 'external_url' => $item['absolute_url'] ?? null,
                'posted_at' => $item['updated_at'] ?? now(),
            ]);
        })->filter(fn ($job) => $job['external_url'])->values()->all();
    }

    protected function scrapeLever(Company $company): array
    {
        $url = $company->jobs_feed_url ?: "https://api.lever.co/v0/postings/{$company->ats_identifier}?mode=json";
        $payload = json_decode($this->getContent($url), true, 512, JSON_THROW_ON_ERROR);

        return collect($payload)->map(function (array $item) use ($company) {
            $categories = $item['categories'] ?? [];
            return $this->normalizeJob($company, [
                'title' => $item['text'] ?? 'Untitled',
                'description' => strip_tags(($item['descriptionPlain'] ?? '').' '.($item['additionalPlain'] ?? '')),
                'location' => $categories['location'] ?? 'Not specified',
                'job_type' => $categories['commitment'] ?? 'Full-time',
                'external_url' => $item['hostedUrl'] ?? $item['applyUrl'] ?? null,
                'posted_at' => isset($item['createdAt']) ? Carbon::createFromTimestampMs($item['createdAt']) : now(),
            ]);
        })->filter(fn ($job) => $job['external_url'])->values()->all();
    }

    protected function normalizeJob(Company $company, array $job): array
    {
        $searchable = trim(($job['title'] ?? '').' '.($job['description'] ?? ''));
        $job['country'] = $this->countryFromLocation($job['location'] ?? '', $company->country);
        $this->technologies ??= \App\Models\Technology::query()->get();
        $this->categories ??= \App\Models\JobCategory::query()->get();
        $job['technologies'] = $this->technologies->filter(
            fn ($technology) => preg_match('/\\b'.preg_quote($technology->name, '/').'\\b/i', $searchable)
        )->pluck('id')->all();
        $job['categories'] = $this->categories->filter(
            fn ($category) => stripos($searchable, $category->name) !== false
        )->pluck('id')->all();
        return $job;
    }

    protected function countryFromLocation(string $location, string $fallback): string
    {
        if (preg_match('/\b(Mohali|Chandigarh|Zirakpur|Pune|Delhi|Gurugram|Gurgaon|Noida|Faridabad|Mumbai|Bengaluru|Bangalore|Hyderabad|Kolkata|Chennai|Ahmedabad|Jaipur|Indore|Coimbatore|Kochi|Bhubaneswar)\b/i', $location)) {
            return 'India';
        }
        foreach (['India', 'United States' => 'USA', 'USA', 'United Kingdom' => 'UK', 'UK', 'Germany', 'France', 'Canada', 'Singapore', 'Australia', 'Japan'] as $needle => $country) {
            if (is_int($needle)) $needle = $country;
            if (stripos($location, $needle) !== false) return $country;
        }
        return $fallback;
    }

    /**
     * Create or update a job in the database
     */
    protected function createOrUpdateJob(Company $company, array $jobData): Job
    {
        $job = Job::firstOrNew(
            ['company_id' => $company->id, 'external_url' => $jobData['external_url'] ?? null],
            ['title' => $jobData['title'] ?? 'Untitled']
        );

        $job->fill([
            'company_id' => $company->id,
            'title' => $jobData['title'] ?? 'Untitled',
            'description' => $jobData['description'] ?? null,
            'location' => $jobData['location'] ?? 'Not specified',
            'country' => $jobData['country'] ?? $company->country,
            'salary_min' => $jobData['salary_min'] ?? null,
            'salary_max' => $jobData['salary_max'] ?? null,
            'job_type' => $jobData['job_type'] ?? 'Full-time',
            'posting_source' => $jobData['posting_source'] ?? 'official_company',
            'work_mode' => $jobData['work_mode'] ?? $this->detectWorkMode($jobData),
            'requirements' => $jobData['requirements'] ?? null,
            'experience_level' => $jobData['experience_level'] ?? null,
            'experience_min' => $jobData['experience_min'] ?? null,
            'experience_max' => $jobData['experience_max'] ?? null,
            'role_family' => $jobData['role_family'] ?? $this->detectRoleFamily($jobData['title'] ?? ''),
            'external_url' => $jobData['external_url'] ?? null,
            'posted_at' => $jobData['posted_at'] ?? now(),
            'expires_at' => $jobData['expires_at'] ?? null,
            'scraped_at' => now(),
            'is_active' => true,
        ]);

        $job->save();

        // Attach technologies if provided
        if (!empty($jobData['technologies'])) {
            $job->technologies()->sync($jobData['technologies']);
        }

        // Attach categories if provided
        if (!empty($jobData['categories'])) {
            $job->categories()->sync($jobData['categories']);
        }

        return $job;
    }

    protected function detectWorkMode(array $job): ?string
    {
        $text = strtolower(($job['title'] ?? '').' '.($job['description'] ?? '').' '.($job['location'] ?? ''));
        if (str_contains($text, 'hybrid')) return 'hybrid';
        if (preg_match('/\b(remote|work from home|wfh)\b/', $text)) return 'remote';
        if (str_contains($text, 'on-site') || str_contains($text, 'onsite')) return 'office';
        return null;
    }

    protected function detectRoleFamily(string $title): ?string
    {
        $roles = [
            'quality-testing' => '/\b(qa|quality|tester|testing|sdet)\b/i',
            'frontend' => '/\b(front[ -]?end|react|angular|ui engineer)\b/i',
            'backend' => '/\b(back[ -]?end|server-side|spring boot|node\.js)\b/i',
            'full-stack' => '/\bfull[ -]?stack\b/i',
            'devops-cloud' => '/\b(devops|sre|cloud|platform engineer)\b/i',
            'data-ai' => '/\b(data|machine learning|ml engineer|artificial intelligence|ai engineer)\b/i',
            'security' => '/\b(security|cyber|soc analyst)\b/i',
            'software-development' => '/\b(developer|software engineer|programmer)\b/i',
        ];
        foreach ($roles as $role => $pattern) if (preg_match($pattern, $title)) return $role;
        return null;
    }

    /**
     * Get HTTP content from URL
     */
    protected function getContent(string $url): string
    {
        try {
            $response = $this->httpClient->get($url);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch content from {$url}: " . $e->getMessage());
        }
    }

    protected function postJson(string $url, string $body): string
    {
        try {
            $response = $this->httpClient->post($url, [
                'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'body' => $body,
            ]);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch content from {$url}: ".$e->getMessage());
        }
    }

}
