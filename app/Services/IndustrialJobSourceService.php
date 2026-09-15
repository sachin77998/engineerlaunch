<?php

namespace App\Services;

use App\Models\Company;
use App\Models\IndustrialCompany;
use App\Models\IndustrialDepartment;
use App\Models\IndustrialJob;
use App\Models\IndustrialJobRole;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class IndustrialJobSourceService
{
    /**
     * Import one generic Job into the industrial job directory.
     *
     * Generic Job
     *      ↓
     * Industrial Company
     *      ↓
     * Industrial Area
     *      ↓
     * Department
     *      ↓
     * Job Role
     *      ↓
     * Industrial Job
     */
    public function ingest(Job $job): ?IndustrialJob
    {
        if (!$job->is_active) {return null;}
        if (empty($job->external_url)) {return null;}
        $genericCompany = $job->relationLoaded('company')? $job->company : $job->company()->first();
        if (!$genericCompany) {return null;}
        $industrialCompany = $this->findIndustrialCompany($genericCompany,$job);
        if (!$industrialCompany) {return null;}
        $department = $this->findDepartment($job);
        $role = $this->findRole($job, $department);
        $externalId = $this->externalId($job);
        $payload = [
            'industrial_area_id' => $industrialCompany->industrial_area_id,
            'industrial_company_id' => $industrialCompany->id,
            'department_id' => $department?->id,
            'job_role_id' => $role?->id,
            'external_id' => $externalId,
            'job_title' => trim((string) $job->title),
            'employment_type' => $this->employmentType($job->job_type),
            'experience_min' => $job->experience_min,
            'experience_max' => $job->experience_max,
            'salary_min' => $job->salary_min,
            'salary_max' => $job->salary_max,
            'salary_period' => $this->salaryPeriod($job),
            'qualification' => $this->qualification($job),
            'skills' => $this->skills($job),
            'description' => $this->description($job),
            'source_url' => $job->external_url,
            'application_deadline' => $job->application_deadline ?? $job->expires_at,
            'is_verified' => $this->isVerifiedSource($job),
            'is_active' => true,
            'source_name' => $this->sourceName($job),
            'last_verified_at' => now(),
            /*
             * Do not mark third-party jobs as admin verified.
             *
             * Official/company source:
             *      company_source
             *
             * Everything else:
             *      unverified
             */
            'verification_status' => $this->verificationStatus($job),
        ];

        $industrialJob = IndustrialJob::updateOrCreate(
            [
                'external_id' => $externalId,
            ],
            $payload
        );

        return $industrialJob->fresh([
            'area',
            'company',
            'department',
            'role',
        ]);
    }

    /**
     * Import multiple generic jobs.
     */
    public function ingestMany(iterable $jobs): array
    {
        $processed = 0;
        $imported = 0;
        $skipped = 0;

        foreach ($jobs as $job) {
            $processed++;

            try {
                $industrialJob = $job instanceof Job
                    ? $this->ingest($job)
                    : null;

                if ($industrialJob) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                report($e);
                $skipped++;
            }
        }

        return [
            'processed' => $processed,
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    /**
     * Find an IndustrialCompany corresponding to the generic Company.
     *
     * Matching strategy:
     *
     * 1. Exact normalized company name
     * 2. Exact normalized plant name
     * 3. Normalized company name contains / contained by
     * 4. Website domain
     * 5. Name similarity + location
     *
     * We NEVER create an IndustrialCompany automatically.
     */
    private function findIndustrialCompany(
        Company $company,
        Job $job
    ): ?IndustrialCompany {
        $companyName = trim((string) $company->name);

        if ($companyName === '') {
            return null;
        }

        $normalizedCompany = $this->normalizeCompanyName(
            $companyName
        );

        /*
         * Load visible industrial companies once.
         *
         * The current directory contains a relatively small verified
         * company master, so this is much safer than performing many
         * database queries for every job.
         */
        $companies = IndustrialCompany::query()
            ->visible()
            ->with([
                'area',
                'area.state',
            ])
            ->get();

        if ($companies->isEmpty()) {
            return null;
        }

        /*
         * ---------------------------------------------------------
         * 1. Exact normalized company name
         * ---------------------------------------------------------
         */
        foreach ($companies as $industrialCompany) {
            if (
                $normalizedCompany ===
                $this->normalizeCompanyName($industrialCompany->name)
            ) {
                return $industrialCompany;
            }
        }

        /*
         * ---------------------------------------------------------
         * 2. Exact normalized plant name
         * ---------------------------------------------------------
         */
        foreach ($companies as $industrialCompany) {
            if (
                !empty($industrialCompany->plant_name) &&
                $normalizedCompany ===
                $this->normalizeCompanyName(
                    $industrialCompany->plant_name
                )
            ) {
                return $industrialCompany;
            }
        }

        /*
         * ---------------------------------------------------------
         * 3. Containment matching
         *
         * Example:
         *
         * Generic:
         *     Happy Forgings Limited
         *
         * Industrial:
         *     Happy Forgings
         *
         * Generic:
         *     Bosch Limited
         *
         * Industrial:
         *     Bosch
         * ---------------------------------------------------------
         */
        foreach ($companies as $industrialCompany) {
            $industrialName = $this->normalizeCompanyName(
                $industrialCompany->name
            );

            if ($industrialName === '') {
                continue;
            }

            if (
                Str::contains($normalizedCompany, $industrialName) ||
                Str::contains($industrialName, $normalizedCompany)
            ) {
                return $industrialCompany;
            }

            if (!empty($industrialCompany->plant_name)) {
                $plantName = $this->normalizeCompanyName(
                    $industrialCompany->plant_name
                );

                if (
                    $plantName !== '' &&
                    (
                        Str::contains($normalizedCompany, $plantName) ||
                        Str::contains($plantName, $normalizedCompany)
                    )
                ) {
                    return $industrialCompany;
                }
            }
        }

        /*
         * ---------------------------------------------------------
         * 4. Website domain matching
         * ---------------------------------------------------------
         */
        if (!empty($company->website)) {
            $domain = $this->domain($company->website);

            if ($domain) {
                foreach ($companies as $industrialCompany) {
                    if (empty($industrialCompany->website)) {
                        continue;
                    }

                    $industrialDomain = $this->domain(
                        $industrialCompany->website
                    );

                    if (
                        $industrialDomain &&
                        $industrialDomain === $domain
                    ) {
                        return $industrialCompany;
                    }
                }
            }
        }

        /*
         * ---------------------------------------------------------
         * 5. Name similarity + location matching
         *
         * This is deliberately more controlled than blindly using
         * similar_text().
         * ---------------------------------------------------------
         */
        $location = trim((string) $job->location);

        $bestCandidate = null;
        $bestScore = 0;

        foreach ($companies as $industrialCompany) {
            $industrialName = $this->normalizeCompanyName(
                $industrialCompany->name
            );

            if ($industrialName === '') {
                continue;
            }

            $score = $this->companySimilarityScore(
                $normalizedCompany,
                $industrialName
            );

            /*
             * Plant name can provide additional evidence.
             */
            if (!empty($industrialCompany->plant_name)) {
                $plantName = $this->normalizeCompanyName(
                    $industrialCompany->plant_name
                );

                if ($plantName !== '') {
                    $plantScore = $this->companySimilarityScore(
                        $normalizedCompany,
                        $plantName
                    );

                    $score = max($score, $plantScore);
                }
            }

            /*
             * Location provides an additional 25 points.
             */
            if (
                $location !== '' &&
                $this->locationMatches(
                    $location,
                    $industrialCompany->area
                )
            ) {
                $score += 25;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestCandidate = $industrialCompany;
            }
        }

        /*
         * Require reasonable confidence.
         *
         * This prevents unrelated companies from being assigned
         * merely because their names happen to be somewhat similar.
         */
        if ($bestCandidate && $bestScore >= 75) {
            return $bestCandidate;
        }

        return null;
    }

    /**
     * Normalize company names for matching.
     */
    private function normalizeCompanyName(string $name): string
    {
        $name = Str::lower(trim($name));

        if ($name === '') {
            return '';
        }

        /*
         * Replace punctuation with spaces.
         */
        $name = preg_replace('/[^\pL\pN]+/u', ' ', $name);

        /*
         * Remove common corporate suffixes.
         */
        $suffixes = [
            'private limited',
            'pvt ltd',
            'pvt limited',
            'private ltd',
            'limited',
            'ltd',
            'llp',
            'incorporated',
            'inc',
            'corporation',
            'corp',
            'company',
            'co',
        ];

        foreach ($suffixes as $suffix) {
            $pattern = '/\b' . preg_quote($suffix, '/') . '\b/u';

            $name = preg_replace($pattern, ' ', $name);
        }

        /*
         * Normalize common ampersand wording.
         */
        $name = str_replace('&', ' and ', $name);

        /*
         * Collapse whitespace.
         */
        $name = preg_replace('/\s+/u', ' ', $name);

        return trim($name);
    }

    /**
     * Calculate company similarity.
     *
     * Returns a score from 0-100.
     */
    private function companySimilarityScore(
        string $first,
        string $second
    ): int {
        if ($first === '' || $second === '') {
            return 0;
        }

        if ($first === $second) {
            return 100;
        }

        if (
            Str::contains($first, $second) ||
            Str::contains($second, $first)
        ) {
            return 90;
        }

        similar_text(
            $first,
            $second,
            $percentage
        );

        return (int) round($percentage);
    }

    /**
     * Find the best industrial department from the generic job.
     */
    private function findDepartment(Job $job): ?IndustrialDepartment
    {
        $text = $this->searchText($job);

        if ($text === '') {
            return null;
        }

        $departments = IndustrialDepartment::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $bestDepartment = null;
        $bestScore = 0;

        foreach ($departments as $department) {
            $name = trim((string) $department->name);

            if ($name === '') {
                continue;
            }

            if ($this->containsTerm($text, $name)) {
                /*
                 * Longer department names are generally more specific.
                 */
                $score = mb_strlen($name);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestDepartment = $department;
                }
            }
        }

        return $bestDepartment;
    }

    /**
     * Find the best industrial role.
     *
     * Prefer roles belonging to the detected department.
     */
    private function findRole(
        Job $job,
        ?IndustrialDepartment $department
    ): ?IndustrialJobRole {
        $text = $this->searchText($job);

        if ($text === '') {
            return null;
        }

        $query = IndustrialJobRole::query()
            ->where('is_active', true)
            ->with('aliases');

        if ($department) {
            $query->where('department_id', $department->id);
        }

        $roles = $query
            ->orderBy('name')
            ->get();

        $bestRole = null;
        $bestScore = 0;

        /*
         * Exact role names.
         */
        foreach ($roles as $role) {
            $roleName = trim((string) $role->name);

            if (
                $roleName !== '' &&
                $this->containsTerm($text, $roleName)
            ) {
                $score = mb_strlen($roleName) + 100;

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestRole = $role;
                }
            }
        }

        if ($bestRole) {
            return $bestRole;
        }

        /*
         * Role aliases.
         */
        foreach ($roles as $role) {
            foreach ($role->aliases as $alias) {
                $aliasName = trim((string) $alias->name);

                if (
                    $aliasName !== '' &&
                    $this->containsTerm($text, $aliasName)
                ) {
                    $score = mb_strlen($aliasName) + 50;

                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestRole = $role;
                    }
                }
            }
        }

        return $bestRole;
    }

    /**
     * Build searchable job text.
     */
    private function searchText(Job $job): string
    {
        $requirements = $job->requirements;

        if (is_array($requirements)) {
            $requirements = implode(
                ' ',
                array_map('strval', $requirements)
            );
        }

        return Str::lower(
            trim(
                implode(
                    ' ',
                    array_filter([
                        $job->title,
                        $job->role,
                        $job->category,
                        $job->department,
                        $job->description,
                        $requirements,
                        $job->location,
                    ])
                )
            )
        );
    }

    /**
     * Check whether a complete term exists in text.
     */
    private function containsTerm(
        string $text,
        string $term
    ): bool {
        $term = Str::lower(trim($term));

        if ($term === '') {
            return false;
        }

        return (bool) preg_match(
            '/(?<![\pL\pN])' .
                preg_quote($term, '/') .
                '(?![\pL\pN])/iu',
            $text
        );
    }

    /**
     * Build a stable industrial external ID.
     */
    private function externalId(Job $job): string
    {
        if (!empty($job->external_job_id)) {
            $source = $job->source ?: $job->posting_source ?: 'generic';

            return 'generic:' .
                Str::slug((string) $source) .
                ':' .
                $job->external_job_id;
        }

        if (!empty($job->external_url)) {
            return 'url:' . hash(
                'sha256',
                $job->external_url
            );
        }

        return 'job:' . $job->id;
    }

    /**
     * Normalize employment type.
     */
    private function employmentType(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        return Str::upper(trim($value));
    }

    /**
     * Determine salary period.
     */
    private function salaryPeriod(Job $job): string
    {
        if (!empty($job->salary_period)) {
            return Str::lower(
                (string) $job->salary_period
            );
        }

        if (
            !empty($job->salary_type) &&
            Str::contains(
                Str::lower((string) $job->salary_type),
                'annual'
            )
        ) {
            return 'annual';
        }

        return 'monthly';
    }

    /**
     * Extract qualification.
     */
    private function qualification(Job $job): ?string
    {
        if (!empty($job->education)) {
            return is_array($job->education)
                ? implode(', ', $job->education)
                : (string) $job->education;
        }

        return null;
    }

    /**
     * Extract skills.
     */
    private function skills(Job $job): array
    {
        $skills = [];

        if (is_array($job->requirements)) {
            $skills = array_merge(
                $skills,
                $job->requirements
            );
        }

        if ($job->relationLoaded('technologies')) {
            foreach ($job->technologies as $technology) {
                if (!empty($technology->name)) {
                    $skills[] = $technology->name;
                }
            }
        }

        return collect($skills)
            ->map(
                fn($skill) => trim((string) $skill)
            )
            ->filter()
            ->unique(
                fn($skill) => Str::lower($skill)
            )
            ->values()
            ->all();
    }

    /**
     * Build description.
     */
    private function description(Job $job): ?string
    {
        if (!empty($job->description)) {
            return trim((string) $job->description);
        }

        return null;
    }

    /**
     * Determine whether the source can be treated as verified.
     */
    private function isVerifiedSource(Job $job): bool
    {
        return in_array(
            Str::lower((string) $job->posting_source),
            [
                'official_company',
                'company',
            ],
            true
        );
    }

    /**
     * Industrial source name.
     */
    private function sourceName(Job $job): string
    {
        if (
            in_array(
                Str::lower((string) $job->posting_source),
                [
                    'official_company',
                    'company',
                ],
                true
            )
        ) {
            return 'company_career_source';
        }

        return 'job_platform';
    }

    /**
     * Verification status.
     *
     * Third-party job platforms must NOT be marked
     * admin_verified automatically.
     */
    private function verificationStatus(Job $job): string
    {
        return $this->isVerifiedSource($job)
            ? 'company_source'
            : 'unverified';
    }

    /**
     * Extract domain from URL.
     */
    private function domain(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $host = parse_url(
            trim($url),
            PHP_URL_HOST
        );

        if (!$host) {
            return null;
        }

        $host = Str::lower(
            preg_replace(
                '/^www\./i',
                '',
                $host
            )
        );

        return $host ?: null;
    }

    /**
     * Check whether a job location belongs to an industrial area.
     */
    private function locationMatches(
        string $location,
        $area
    ): bool {
        if (!$area) {
            return false;
        }

        $location = Str::lower(
            trim($location)
        );

        $location = preg_replace(
            '/[,\-\/]+/',
            ' ',
            $location
        );

        foreach (
            [
                $area->name,
                $area->city,
                $area->district,
            ] as $value
        ) {
            if (!$value) {
                continue;
            }

            $value = Str::lower(
                trim((string) $value)
            );

            if ($value === '') {
                continue;
            }

            if (Str::contains($location, $value)) {
                return true;
            }
        }

        if (
            $area->state &&
            !empty($area->state->name)
        ) {
            $state = Str::lower(
                trim((string) $area->state->name)
            );

            if (
                $state !== '' &&
                Str::contains($location, $state)
            ) {
                return true;
            }
        }

        return false;
    }
}
