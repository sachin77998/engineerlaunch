<?php

namespace App\Console\Commands;

use App\Services\Technologies\TechnologyCatalog;
use Illuminate\Console\Command;
use Throwable;

class SyncTechnologies extends Command
{
    protected $signature = 'technologies:sync {--skip-discovery : Only re-match jobs to the technologies already stored}';
    protected $description = 'Discover programming languages, frameworks, databases and tools (Wikidata + skill taxonomy) and link jobs to them';

    public function handle(TechnologyCatalog $catalog): int
    {
        if (!$this->option('skip-discovery')) {
            try {
                $found = $catalog->discover();
                $this->info("Technologies: {$found['created']} new, {$found['updated']} updated.");
            } catch (Throwable $e) {
                // Keep going: linking jobs to the technologies already stored is still useful.
                $this->warn('Discovery failed: ' . $e->getMessage());
            }
        }
        $linked = $catalog->linkJobs();
        $this->info("Matched {$linked['jobs']} jobs: {$linked['links']} new links, {$linked['technologies']} technologies with openings.");
        return self::SUCCESS;
    }
}
