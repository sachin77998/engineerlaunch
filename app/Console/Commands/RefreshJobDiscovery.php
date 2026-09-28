<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\JobScraper;
use App\Support\DiscoveryCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RefreshJobDiscovery extends Command
{
    protected $signature = 'jobs:refresh-discovery {--all : Refresh every enabled source instead of the six new feeds} {--prepare-only : Seed directories and enrich existing jobs without network fetching}';
    protected $description = 'Seed skills and official sources, fetch current jobs, and rebuild search filters';

    public function handle(JobScraper $scraper): int
    {
        $lock = Cache::lock('jobs:refresh-discovery', 21600);
        if (! $lock->get()) { $this->warn('A discovery refresh is already running.'); return self::FAILURE; }
        try {
            foreach (['TechnologySeeder', 'OfficialCareerSourceSeeder', 'CyberCityCompanySeeder', 'CareerExplorerSeeder'] as $seeder) {
                if ($this->call('db:seed', ['--class' => $seeder, '--force' => true]) !== self::SUCCESS) return self::FAILURE;
            }
            $failures = 0;
            if (! $this->option('prepare-only')) {
                $companies = Company::active()->where('sync_enabled', true)
                    ->when(! $this->option('all'), fn ($q) => $q->whereIn('ats_identifier', ['mongodb','grafanalabs','canonical','datadog','cloudflare','discord']))
                    ->orderBy('id')->get();
                foreach ($companies as $company) {
                    $result = $scraper->scrapeCompany($company);
                    $this->line($company->name.': '.json_encode(collect($result)->except('exception')->all(), JSON_UNESCAPED_SLASHES));
                    if (! $result['success'] || !empty($result['errors'])) $failures++;
                }
            }
            $enrichment = $this->call('jobs:enrich-filters');
            DiscoveryCache::invalidate();
            return $failures || $enrichment !== self::SUCCESS ? self::FAILURE : self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
