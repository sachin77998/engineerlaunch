<?php

namespace App\Services\JobFeeds;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Public job feeds normalised to one row shape:
 * source, external_id, title, company, location, country, description, url, posted_at, salary_min, salary_max, salary_currency, job_type, work_mode, tags.
 */
class JobFeeds
{
    public function available(): array
    {
        return array_keys(array_filter([
            'arbeitnow' => true,
            'remotive' => true,
            'adzuna' => filled(config('services.adzuna.app_id')) && filled(config('services.adzuna.app_key')),
            'jooble' => filled(config('services.jooble.key')),
        ]));
    }

    public function fetch(string $feed, array $params): array
    {
        return match ($feed) {
            'arbeitnow' => $this->arbeitnow((int) ($params['page'] ?? 1)),
            'remotive' => $this->remotive($params['query'] ?? ''),
            'adzuna' => $this->adzuna($params['query'], $params['where'] ?? null, $params['country'] ?? 'in', (int) ($params['page'] ?? 1)),
            'jooble' => $this->jooble($params['query'], $params['where'] ?? ''),
            default => throw new RuntimeException("Unknown job feed {$feed}."),
        };
    }

    private function arbeitnow(int $page): array
    {
        $payload = $this->http()->get('https://www.arbeitnow.com/api/job-board-api', ['page' => $page])->throw()->json('data', []);
        return array_map(fn ($job) => [
            'source' => 'arbeitnow', 'external_id' => $job['slug'], 'title' => $job['title'], 'company' => $job['company_name'],
            'location' => $job['location'] ?: ($job['remote'] ? 'Remote' : 'Germany'), 'country' => 'Germany',
            'description' => $job['description'] ?? null, 'url' => $job['url'],
            'posted_at' => isset($job['created_at']) ? Carbon::createFromTimestamp($job['created_at']) : now(),
            'job_type' => $job['job_types'][0] ?? 'Full Time', 'work_mode' => !empty($job['remote']) ? 'Remote' : 'On-site', 'tags' => $job['tags'] ?? [],
        ], $payload);
    }

    private function remotive(string $query): array
    {
        $payload = $this->http()->get('https://remotive.com/api/remote-jobs', array_filter(['search' => $query, 'limit' => 100]))->throw()->json('jobs', []);
        return array_map(fn ($job) => [
            'source' => 'remotive', 'external_id' => (string) $job['id'], 'title' => $job['title'], 'company' => $job['company_name'],
            'location' => $job['candidate_required_location'] ?: 'Remote', 'country' => 'Worldwide',
            'description' => $job['description'] ?? null, 'url' => $job['url'], 'posted_at' => Carbon::parse($job['publication_date'] ?? 'now'),
            'job_type' => str_replace('_', ' ', $job['job_type'] ?? 'full_time'), 'work_mode' => 'Remote', 'tags' => array_merge([$job['category'] ?? null], $job['tags'] ?? []),
        ], $payload);
    }

    private function adzuna(string $query, ?string $where, string $country, int $page): array
    {
        $payload = $this->http()->get("https://api.adzuna.com/v1/api/jobs/{$country}/search/{$page}", array_filter([
            'app_id' => config('services.adzuna.app_id'), 'app_key' => config('services.adzuna.app_key'),
            'what' => $query, 'where' => $where, 'results_per_page' => 50, 'max_days_old' => 30, 'content-type' => 'application/json',
        ]))->throw()->json('results', []);
        $currency = ['in' => 'INR', 'gb' => 'GBP', 'us' => 'USD', 'de' => 'EUR', 'fr' => 'EUR', 'ca' => 'CAD', 'au' => 'AUD', 'sg' => 'SGD'][$country] ?? 'INR';
        return array_map(fn ($job) => [
            'source' => 'adzuna', 'external_id' => (string) $job['id'], 'title' => $job['title'], 'company' => $job['company']['display_name'] ?? 'Employer (via Adzuna)',
            'location' => $job['location']['display_name'] ?? ($where ?: 'India'), 'country' => $country === 'in' ? 'India' : strtoupper($country),
            'description' => $job['description'] ?? null, 'url' => $job['redirect_url'], 'posted_at' => Carbon::parse($job['created'] ?? 'now'),
            'salary_min' => $job['salary_min'] ?? null, 'salary_max' => $job['salary_max'] ?? null, 'salary_currency' => $currency,
            'job_type' => ($job['contract_time'] ?? '') === 'part_time' ? 'Part Time' : 'Full Time', 'work_mode' => 'On-site', 'tags' => array_filter([$job['category']['label'] ?? null, $query]),
        ], $payload);
    }

    private function jooble(string $query, string $where): array
    {
        // Jooble keys are issued per country site: jooble.org (worldwide/US), in.jooble.org (India), uk.jooble.org ...
        $host = config('services.jooble.host');
        $country = config('services.jooble.country');
        $payload = $this->http()->post("https://{$host}/api/" . config('services.jooble.key'), array_filter(['keywords' => $query, 'location' => $where]))->throw()->json('jobs', []);
        return array_map(fn ($job) => [
            'source' => 'jooble', 'external_id' => (string) ($job['id'] ?? md5($job['link'] ?? $job['title'] ?? '')), 'title' => $job['title'] ?? '', 'company' => ($job['company'] ?? '') ?: 'Employer (via Jooble)',
            'location' => ($job['location'] ?? '') ?: ($where ?: $country), 'country' => $country, 'description' => $job['snippet'] ?? null, 'url' => $job['link'] ?? null,
            'posted_at' => Carbon::parse($job['updated'] ?? 'now'), 'job_type' => ($job['type'] ?? '') ?: 'Full Time', 'work_mode' => 'On-site', 'tags' => [$query],
        ], $payload);
    }

    private function http(): PendingRequest
    {
        return Http::withHeaders(['User-Agent' => config('discovery.user_agent'), 'Accept' => 'application/json'])->timeout(45)->retry(2, 3000);
    }
}
