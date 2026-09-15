<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Job;
use App\Services\IndustrialJobSourceService;
use App\Services\JobScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ScrapeJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:scrape
        {--company= : Sync one configured company by ID}
        {--register= : Register a public Greenhouse or Lever careers URL}
        {--name= : Company name used with --register}
        {--country= : Company home country used with --register}
        {--industrial : Also synchronize fetched jobs into the industrial job directory}
        {--industrial-limit= : Maximum generic jobs to map into the industrial directory}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Scrape real job listings from company career pages and optionally synchronize them with the industrial job directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('register')) {
            return $this->registerSource();
        }

        $scraper = app(JobScraper::class);

        $companyId = $this->option('company');

        if ($companyId) {
            $company = Company::active()->find($companyId);

            if (!$company) {
                $this->error("Company not found with ID: {$companyId}");

                return self::FAILURE;
            }

            $companies = collect([$company]);
        } else {
            $companies = Company::active()
                ->where('sync_enabled', true)
                ->orderBy('id')
                ->get();
        }

        if ($companies->isEmpty()) {
            $this->warn('No active companies are configured for job synchronization.');

            return self::SUCCESS;
        }

        $this->info(
            "Starting to scrape {$companies->count()} companies..."
        );

        $this->newLine();

        $totalResults = [
            'jobs_found' => 0,
            'jobs_added' => 0,
            'jobs_updated' => 0,
            'failed' => 0,
        ];

        foreach ($companies as $company) {
            $this->info("Scraping: {$company->name}");

            try {
                $results = $scraper->scrapeCompany($company);

                if ($results['success']) {
                    $this->line(
                        "  ✓ Found: {$results['jobs_found']} | " .
                            "Added: {$results['jobs_added']} | " .
                            "Updated: {$results['jobs_updated']}"
                    );

                    $totalResults['jobs_found'] += $results['jobs_found'];
                    $totalResults['jobs_added'] += $results['jobs_added'];
                    $totalResults['jobs_updated'] += $results['jobs_updated'];
                } else {
                    $errors = $results['errors'] ?? [];

                    $this->error(
                        "  ✗ Failed: " .
                            ($errors
                                ? implode(', ', $errors)
                                : 'Unknown scraping error')
                    );

                    $totalResults['failed']++;
                }
            } catch (\Throwable $e) {
                report($e);

                $this->error(
                    "  ✗ Exception: {$e->getMessage()}"
                );

                $totalResults['failed']++;
            }
        }

        $this->newLine();

        $this->info(str_repeat('=', 60));
        $this->info('JOB SCRAPING RESULTS');
        $this->info(str_repeat('=', 60));

        $this->line(
            "Jobs found    : {$totalResults['jobs_found']}"
        );

        $this->line(
            "Jobs added    : {$totalResults['jobs_added']}"
        );

        $this->line(
            "Jobs updated  : {$totalResults['jobs_updated']}"
        );

        $this->line(
            "Failed        : {$totalResults['failed']}"
        );

        $this->info(str_repeat('=', 60));

        /*
         * ---------------------------------------------------------
         * INDUSTRIAL JOB SYNCHRONIZATION
         * ---------------------------------------------------------
         *
         * This is intentionally optional so the existing generic
         * job scraper continues to work exactly as before.
         *
         * Example:
         *
         * php artisan jobs:scrape --industrial
         *
         * Or:
         *
         * php artisan jobs:scrape --industrial --industrial-limit=500
         */
        if ($this->option('industrial')) {
            $this->newLine();

            return $this->syncIndustrialJobs();
        }

        return $totalResults['failed'] > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Synchronize generic jobs with the industrial directory.
     */
    protected function syncIndustrialJobs(): int
    {
        $this->info(str_repeat('=', 60));
        $this->info('INDUSTRIAL JOB SYNCHRONIZATION');
        $this->info(str_repeat('=', 60));

        $limit = $this->option('industrial-limit');

        $query = Job::query()
            ->active()
            ->with([
                'company',
                'technologies',
            ])
            ->orderBy('id');

        if ($limit !== null && (int) $limit > 0) {
            $query->limit((int) $limit);
        }

        $service = app(IndustrialJobSourceService::class);

        $processed = 0;
        $imported = 0;
        $skipped = 0;
        $failed = 0;

        /*
         * When a limit is supplied, get() is sufficient.
         *
         * Without a limit, process in chunks so the command can
         * eventually handle a very large job catalogue.
         */
        if ($limit !== null && (int) $limit > 0) {
            $jobs = $query->get();

            foreach ($jobs as $job) {
                $processed++;

                try {
                    $industrialJob = $service->ingest($job);

                    if ($industrialJob) {
                        $imported++;

                        $this->line(
                            "  ✓ {$job->title} → imported"
                        );
                    } else {
                        $skipped++;

                        $this->line(
                            "  - {$job->title} → skipped"
                        );
                    }
                } catch (\Throwable $e) {
                    $failed++;

                    report($e);

                    $this->error(
                        "  ✗ {$job->title} → {$e->getMessage()}"
                    );
                }
            }
        } else {
            $query->chunkById(100, function ($jobs) use (
                $service,
                &$processed,
                &$imported,
                &$skipped,
                &$failed
            ) {
                foreach ($jobs as $job) {
                    $processed++;

                    try {
                        $industrialJob = $service->ingest($job);

                        if ($industrialJob) {
                            $imported++;

                            $this->line(
                                "  ✓ {$job->title} → imported"
                            );
                        } else {
                            $skipped++;

                            $this->line(
                                "  - {$job->title} → skipped"
                            );
                        }
                    } catch (\Throwable $e) {
                        $failed++;

                        report($e);

                        $this->error(
                            "  ✗ {$job->title} → {$e->getMessage()}"
                        );
                    }
                }
            });
        }

        $this->newLine();

        $this->info(str_repeat('=', 60));
        $this->info('INDUSTRIAL JOB RESULTS');
        $this->info(str_repeat('=', 60));

        $this->line(
            "Jobs processed : {$processed}"
        );

        $this->line(
            "Jobs imported  : {$imported}"
        );

        $this->line(
            "Jobs skipped   : {$skipped}"
        );

        $this->line(
            "Jobs failed    : {$failed}"
        );

        $this->info(str_repeat('=', 60));

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * Register a public Greenhouse or Lever careers URL.
     */
    protected function registerSource(): int
    {
        $url = rtrim(
            (string) $this->option('register'),
            '/'
        );

        $name = trim(
            (string) $this->option('name')
        );

        if ($name === '') {
            $this->error(
                'The --name option is required when registering a source.'
            );

            return self::FAILURE;
        }

        $provider = null;
        $identifier = null;

        /*
         * Greenhouse:
         *
         * https://boards.greenhouse.io/company
         * https://job-boards.greenhouse.io/company
         */
        if (
            preg_match(
                '~(?:boards|job-boards)\.greenhouse\.io/(?:embed/job_board\?for=)?([^/?#]+)~i',
                $url,
                $match
            )
        ) {
            $provider = 'greenhouse';
            $identifier = $match[1];
        }

        /*
         * Lever:
         *
         * https://jobs.lever.co/company
         */ elseif (
            preg_match(
                '~jobs\.lever\.co/([^/?#]+)~i',
                $url,
                $match
            )
        ) {
            $provider = 'lever';
            $identifier = $match[1];
        }

        if (!$provider || !$identifier) {
            $this->error(
                'Unsupported careers URL. Greenhouse and Lever are currently supported by this registration command.'
            );

            return self::FAILURE;
        }

        $company = Company::updateOrCreate(
            [
                'slug' => Str::slug($name),
            ],
            [
                'name' => $name,
                'careers_url' => $url,
                'country' => $this->option('country') ?: 'Global',
                'ats_provider' => $provider,
                'ats_identifier' => $identifier,
                'sync_enabled' => true,
                'is_active' => true,
            ]
        );

        $this->info(
            "Registered {$company->name} ({$provider}: {$identifier})."
        );

        /*
         * Immediately fetch the company's real jobs.
         */
        $exitCode = $this->call(
            'jobs:scrape',
            [
                '--company' => $company->id,
                '--industrial' => true,
            ]
        );

        return $exitCode;
    }
}
