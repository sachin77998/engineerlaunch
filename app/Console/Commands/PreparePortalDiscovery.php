<?php
namespace App\Console\Commands;

use App\Support\DiscoveryCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PreparePortalDiscovery extends Command
{
    protected $signature = 'portal:prepare-discovery';
    protected $description = 'Idempotently prepare career tables and skills before the full deployment migration chain';

    public function handle(): int
    {
        $lock = Cache::lock('portal:prepare-discovery', 300);
        if (!$lock->get()) { $this->error('Discovery preparation is already running.'); return self::FAILURE; }
        try {
            if (!Schema::hasTable('companies') || !Schema::hasTable('technologies')) {
                $this->error('Base company/technology tables are missing; run the initial installation migrations first.');
                return self::FAILURE;
            }
            // This migration only creates missing career tables and never drops data.
            (require database_path('migrations/2026_09_26_000001_create_career_explorer.php'))->up();
            foreach (['TechnologySeeder', 'CareerExplorerSeeder'] as $seeder)
                if ($this->call('db:seed', ['--class'=>$seeder,'--force'=>true]) !== self::SUCCESS) return self::FAILURE;
            DiscoveryCache::invalidate();
            $this->info('Career tables, career reference data and skills are ready.');
            return self::SUCCESS;
        } finally { $lock->release(); }
    }
}
