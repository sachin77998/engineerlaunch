<?php

namespace App\Services\JobFeeds;

use App\Models\Company;
use App\Models\Job;
use App\Services\Discovery\CompanyClassifier;
use App\Services\SectorCatalogImporter;
use Illuminate\Support\Str;

/** Upserts normalised feed rows as public jobs that link out to the original posting. */
class ExternalJobWriter
{
    public const LIFETIME_DAYS = 45;

    public function __construct(private SectorCatalogImporter $catalog, private CompanyClassifier $classifier) {}

    /** @return array{found:int,created:int,updated:int} */
    public function write(array $rows): array
    {
        $result = ['found' => 0, 'created' => 0, 'updated' => 0];
        foreach ($rows as $row) {
            if (blank($row['title'] ?? null) || blank($row['url'] ?? null) || blank($row['external_id'] ?? null)) continue;
            $company = $this->company(trim($row['company'] ?: 'Confidential employer'), $row);
            $posted = $row['posted_at'] ?? now();
            $job = Job::firstOrNew(['source' => $row['source'], 'external_job_id' => Str::limit($row['external_id'], 180, '')]);
            $job->fill([
                'company_id' => $company->id,
                'title' => Str::limit(trim(strip_tags($row['title'])), 250, ''),
                'description' => $this->text($row['description'] ?? null),
                'location' => Str::limit(trim($row['location'] ?? '') ?: 'Not specified', 250, ''),
                'country' => $row['country'] ?? 'India',
                'salary_min' => $row['salary_min'] ?? null, 'salary_max' => $row['salary_max'] ?? null,
                'salary_currency' => $row['salary_currency'] ?? $this->currency($row['country'] ?? null),
                'salary_period' => isset($row['salary_min']) ? 'annual' : 'year',
                'salary_type' => isset($row['salary_min']) ? 'range' : 'undisclosed',
                'job_type' => Str::limit(Str::headline($row['job_type'] ?? 'Full Time'), 60, ''),
                'work_mode' => $row['work_mode'] ?? 'On-site',
                'posting_source' => 'job_board', 'external_url' => $row['url'],
                'requirements' => array_values(array_slice(array_filter($row['tags'] ?? []), 0, 15)),
                'posted_at' => $posted, 'expires_at' => $posted->copy()->addDays(self::LIFETIME_DAYS), 'scraped_at' => now(),
                'deduplication_key' => hash('sha256', $row['source'] . '|' . $row['external_id']),
                'status' => 'published', 'job_visibility' => 'public', 'is_active' => true, 'application_method' => 'external',
            ]);
            if (!$job->slug) $job->slug = Str::limit(Str::slug($job->title), 150, '') . '-' . substr(hash('crc32b', $row['source'] . $row['external_id']), 0, 8);
            $result[$job->exists ? 'updated' : 'created']++;
            $job->save();
            $result['found']++;
        }
        return $result;
    }

    private function company(string $name, array $row): Company
    {
        $company = Company::where('slug', Str::slug($name))->orWhere('name', $name)->first();
        if ($company) return $company;
        [$sector, $subsector] = $this->classifier->classify($name, $row['title'] ?? '', implode(' ', $row['tags'] ?? []));
        // Employers seen only through a feed are listed but not crawled until an official website is known.
        $company = $this->catalog->upsertCompany(['name' => $name, 'country' => $row['country'] ?? null], $sector, $subsector);
        $this->catalog->attach($company, $sector, $subsector);
        return $company;
    }

    private function currency(?string $country): string
    {
        return match ($country) {
            'India' => 'INR', 'Germany', 'France', 'Netherlands' => 'EUR', 'United Kingdom' => 'GBP', default => 'USD',
        };
    }

    private function text(?string $html): ?string
    {
        if (blank($html)) return null;
        $text = strip_tags(preg_replace('~<(br|/p|/li|/h\d)\s*/?>~i', "\n", $html));
        return Str::limit(trim(preg_replace("/\n{3,}/", "\n\n", html_entity_decode($text, ENT_QUOTES | ENT_HTML5))), 9500, '');
    }
}
