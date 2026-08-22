<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Job;
use App\Models\Technology;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobIngestionService
{
    public function ingest(array $payload): Job
    {
        return DB::transaction(function () use ($payload) {
            $company = Company::firstOrCreate(
                ['slug' => Str::slug($payload['company_name'])],
                ['name' => $payload['company_name'], 'country' => $payload['country'] ?? 'India', 'is_active' => true]
            );

            $source = Str::lower($payload['source']);
            $fingerprint = hash('sha256', implode('|', [
                $source,
                $payload['external_job_id'],
                Str::lower($payload['company_name']),
                Str::lower($payload['title']),
                Str::lower($payload['location']),
            ]));

            $job = Job::updateOrCreate(
                ['source' => $source, 'external_job_id' => $payload['external_job_id']],
                [
                    'company_id' => $company->id,
                    'title' => trim($payload['title']),
                    'description' => $payload['description'] ?? null,
                    'location' => trim($payload['location']),
                    'country' => $payload['country'] ?? 'India',
                    'experience_min' => $payload['experience_min'] ?? null,
                    'experience_max' => $payload['experience_max'] ?? null,
                    'salary_min' => $payload['salary_min'] ?? null,
                    'salary_max' => $payload['salary_max'] ?? null,
                    'salary_currency' => Str::upper($payload['salary_currency'] ?? 'INR'),
                    'job_type' => Str::headline(Str::lower($payload['employment_type'] ?? 'FULL_TIME')),
                    'work_mode' => Str::headline(Str::lower($payload['work_mode'] ?? 'OFFICE')),
                    'posting_source' => $source === 'employer' ? 'company' : 'ats',
                    'external_url' => $payload['apply_url'],
                    'requirements' => $payload['skills'] ?? [],
                    'posted_at' => $payload['posted_at'] ?? now(),
                    'expires_at' => $payload['expires_at'] ?? null,
                    'scraped_at' => now(),
                    'deduplication_key' => $fingerprint,
                    'source_payload' => Arr::except($payload, ['description']),
                    'is_active' => true,
                ]
            );

            $technologyIds = collect($payload['skills'] ?? [])->map(function (string $skill) {
                return Technology::firstOrCreate(['slug' => Str::slug($skill)], ['name' => trim($skill), 'is_active' => true])->id;
            });
            $job->technologies()->sync($technologyIds);

            return $job->fresh(['company', 'technologies']);
        });
    }
}
