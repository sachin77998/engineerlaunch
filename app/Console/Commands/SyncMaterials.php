<?php

namespace App\Console\Commands;

use App\Services\Materials\MaterialCatalogue;
use Illuminate\Console\Command;

class SyncMaterials extends Command
{
    protected $signature = 'materials:sync {--all : Re-read every product page, not only those changed since the last run} {--limit= : Read at most N product pages (testing)}';
    protected $description = 'Home Building: refresh construction material products, prices, stock and bulk tiers from the store catalogue';

    public function handle(MaterialCatalogue $catalogue): int
    {
        $r = $catalogue->sync((bool) $this->option('all'), $this->option('limit') ? (int) $this->option('limit') : null, fn ($m) => $this->line($m));
        $this->info("Saved {$r['saved']} of {$r['fetched']} changed products ({$r['seen']} listed); {$r['failed']} failed, {$r['delisted']} no longer listed.");
        return $r['failed'] && !$r['saved'] ? self::FAILURE : self::SUCCESS;
    }
}
