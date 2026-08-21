<?php

namespace App\Console\Commands;

use App\Models\Company;
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
        {--country= : Company home country used with --register}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Scrape job listings from company career pages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('register')) {
            return $this->registerSource();
        }

        $scraper = new JobScraper();
        $companyId = $this->option('company');

        if ($companyId) {
            // Scrape specific company
            $company = Company::active()->find($companyId);
            if (!$company) {
                $this->error("Company not found with ID: {$companyId}");
                return 1;
            }
            $companies = collect([$company]);
        } else {
            // Scrape all active companies
            $companies = Company::active()->where('sync_enabled', true)->get();
        }

        $this->info("Starting to scrape {$companies->count()} companies...\n");

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
                    $this->line("  ✓ Found: {$results['jobs_found']} | Added: {$results['jobs_added']} | Updated: {$results['jobs_updated']}");
                    $totalResults['jobs_found'] += $results['jobs_found'];
                    $totalResults['jobs_added'] += $results['jobs_added'];
                    $totalResults['jobs_updated'] += $results['jobs_updated'];
                } else {
                    $this->error("  ✗ Failed: " . implode(', ', $results['errors']));
                    $totalResults['failed']++;
                }
            } catch (\Exception $e) {
                $this->error("  ✗ Exception: " . $e->getMessage());
                $totalResults['failed']++;
            }
        }

        $this->info("\n" . str_repeat("=", 50));
        $this->info("TOTAL RESULTS:");
        $this->line("  Jobs Found: {$totalResults['jobs_found']}");
        $this->line("  Jobs Added: {$totalResults['jobs_added']}");
        $this->line("  Jobs Updated: {$totalResults['jobs_updated']}");
        $this->line("  Failed Companies: {$totalResults['failed']}");
        $this->info(str_repeat("=", 50));

        return 0;
    }

    protected function registerSource(): int
    {
        $url = rtrim((string) $this->option('register'), '/');
        $name = trim((string) $this->option('name'));
        if ($name === '') {
            $this->error('The --name option is required when registering a source.');
            return self::FAILURE;
        }

        $provider = null;
        $identifier = null;
        if (preg_match('~(?:boards|job-boards)\.greenhouse\.io/(?:embed/job_board\?for=)?([^/?#]+)~i', $url, $match)) {
            $provider = 'greenhouse';
            $identifier = $match[1];
        } elseif (preg_match('~jobs\.lever\.co/([^/?#]+)~i', $url, $match)) {
            $provider = 'lever';
            $identifier = $match[1];
        }

        if (!$provider || !$identifier) {
            $this->error('Unsupported careers URL. Greenhouse and Lever are currently supported.');
            return self::FAILURE;
        }

        $company = Company::updateOrCreate(['slug' => Str::slug($name)], [
            'name' => $name,
            'careers_url' => $url,
            'country' => $this->option('country') ?: 'Global',
            'ats_provider' => $provider,
            'ats_identifier' => $identifier,
            'sync_enabled' => true,
            'is_active' => true,
        ]);

        $this->info("Registered {$company->name} ({$provider}: {$identifier}).");
        return $this->call('jobs:scrape', ['--company' => $company->id]);
    }
}
