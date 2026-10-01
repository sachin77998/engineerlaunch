<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\CareerSourceDetector;
use App\Services\JobScraper;
use App\Support\DiscoveryCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshJobDiscovery extends Command
{
    protected $signature = 'jobs:refresh-discovery
                            {--all : Refresh every enabled employer source}
                            {--prepare-only : Seed directories and detect sources without fetching jobs}';

    protected $description = 'Seed employer inventory, detect official career sources, fetch current jobs, and rebuild search filters';

    public function handle(
        JobScraper $scraper,
        CareerSourceDetector $detector
    ): int {
        $lock = Cache::lock('jobs:refresh-discovery', 21600);

        if (! $lock->get()) {
            $this->warn(
                'A discovery refresh is already running.'
            );

            return self::FAILURE;
        }

        try {
            /*
             * Load the worldwide employer inventory first.
             *
             * This includes resources/data/requested-employers.json.
             */
            $seeders = [
                'TechnologySeeder',
                'OfficialCareerSourceSeeder',
                'RequestedCompanySourceSeeder',
                'RequestedEmployerInventorySeeder',
                'CyberCityCompanySeeder',
                'CareerExplorerSeeder',
            ];

            foreach ($seeders as $seeder) {
                $result = $this->call(
                    'db:seed',
                    [
                        '--class' => $seeder,
                        '--force' => true,
                    ]
                );

                if ($result !== self::SUCCESS) {
                    return self::FAILURE;
                }
            }

            /*
             * Detect the recruitment platform for companies that do not
             * already have a dedicated configured provider.
             */
            $companies = Company::query()
                ->active()
                ->where('sync_enabled', true)
                ->orderBy('id')
                ->get();

            $detected = 0;
            $unknown = 0;

            foreach ($companies as $company) {
                /*
                 * Never overwrite an explicitly configured provider.
                 *
                 * Examples:
                 * bobo
                 * infosys_algolia
                 * oracle_recruiting
                 * successfactors
                 * workday
                 * greenhouse
                 * lever
                 * smartrecruiters
                 */
                if (
                    filled($company->ats_provider) &&
                    $company->ats_provider !== 'official_discovery'
                ) {
                    continue;
                }

                $source = $detector->detect($company);

                if ($source['provider'] !== null) {
                    $company->forceFill([
                        'ats_provider' => $source['provider'],
                        'ats_identifier' => $source['identifier'],
                        'jobs_feed_url' => $source['jobs_feed_url'],
                        'sync_enabled' => true,
                    ])->save();

                    $detected++;

                    $this->line(
                        '[SOURCE] '
                            . $company->name
                            . ' => '
                            . $source['provider']
                            . ' ['
                            . $source['confidence']
                            . ']'
                    );

                    continue;
                }

                /*
                 * Keep the company available for official discovery.
                 * Do not invent an ATS provider.
                 */
                if ($company->ats_provider !== 'official_discovery') {
                    $company->forceFill([
                        'ats_provider' => 'official_discovery',
                        'sync_enabled' => true,
                    ])->save();
                }

                $unknown++;

                $this->line(
                    '[DISCOVERY] '
                        . $company->name
                        . ' => official_discovery'
                );
            }

            $this->newLine();

            $this->info(
                'Source detection completed: '
                    . $detected
                    . ' providers detected, '
                    . $unknown
                    . ' companies require official discovery.'
            );

            /*
             * --prepare-only stops before network job fetching.
             */
            if ($this->option('prepare-only')) {
                DiscoveryCache::invalidate();

                $enrichment = $this->call(
                    'jobs:enrich-filters'
                );

                return $enrichment === self::SUCCESS
                    ? self::SUCCESS
                    : self::FAILURE;
            }

            /*
             * IMPORTANT:
             *
             * There is no longer a six-company default list.
             *
             * The refresh operates on every enabled employer.
             *
             * --all is retained for backward compatibility with existing
             * cron/cPanel commands.
             */
            $companies = Company::query()
                ->active()
                ->where('sync_enabled', true)
                ->orderBy('id')
                ->get();

            $this->info(
                'Starting job synchronization for '
                    . $companies->count()
                    . ' enabled employers.'
            );

            $failures = 0;
            $successes = 0;
            $totalJobs = 0;

            foreach ($companies as $company) {
                /*
                 * Skip companies without an official source URL.
                 * Do not fabricate a source.
                 */
                if (
                    blank($company->careers_url) &&
                    blank($company->website) &&
                    blank($company->jobs_feed_url)
                ) {
                    $failures++;

                    $this->warn(
                        '[SKIP] '
                            . $company->name
                            . ': no official careers/website/feed URL.'
                    );

                    continue;
                }

                $totals=['jobs_found'=>0,'jobs_added'=>0,'jobs_updated'=>0];
                do {
                    $result = $scraper->scrapeCompany($company->fresh());
                    foreach ($totals as $key=>$value) $totals[$key] += $result[$key] ?? 0;
                    if (!empty($result['continuation'])) $this->line('[CONTINUE] '.$company->name.': '.$totals['jobs_found'].' processed');
                } while (!empty($result['continuation']) && $result['success']);
                $result=array_replace($result,$totals);

                $jobsFound = (int) (
                    $result['jobs_found'] ?? 0
                );

                $totalJobs += $jobsFound;

                if (
                    ! $result['success'] ||
                    ! empty($result['errors'])
                ) {
                    $failures++;

                    $this->error(
                        '[FAILED] '
                            . $company->name
                            . ': '
                            . json_encode(
                                collect($result)
                                    ->except('exception')
                                    ->all(),
                                JSON_UNESCAPED_SLASHES
                            )
                    );

                    continue;
                }

                $successes++;

                $this->line(
                    '[OK] '
                        . $company->name
                        . ': '
                        . json_encode(
                            collect($result)
                                ->except('exception')
                                ->all(),
                            JSON_UNESCAPED_SLASHES
                        )
                );
            }

            DiscoveryCache::invalidate();

            $enrichment = $this->call(
                'jobs:enrich-filters'
            );

            $this->newLine();

            $this->info(
                'Job synchronization completed.'
            );

            $this->table(
                [
                    'Employers',
                    'Successful',
                    'Failed',
                    'Jobs Found',
                ],
                [[
                    $companies->count(),
                    $successes,
                    $failures,
                    $totalJobs,
                ]]
            );

            if ($enrichment !== self::SUCCESS) {
                return self::FAILURE;
            }

            return $failures > 0
                ? self::FAILURE
                : self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
