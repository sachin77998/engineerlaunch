<?php

namespace App\Console\Commands;

use App\Jobs\ImportMcaCompaniesPage;
use App\Services\Insights\DataGovMca;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ImportMcaCompanies extends Command
{
    protected $signature = 'insights:import-mca {--state=* : States to import (default: all)} {--now : Import the first page inline} {--status : Show progress only}';
    protected $description = 'Import the MCA company register (data.gov.in Company Master Data) into mca_companies, state by state on the queue';

    public function handle(DataGovMca $mca): int
    {
        $states = $this->option('state') ?: DataGovMca::STATES;
        if ($this->option('status')) {
            foreach ($states as $state) {
                $p = Cache::get('mca-import:' . $state);
                $this->line(str_pad($state, 30) . (is_array($p) && isset($p['started_at'])
                    ? number_format((int) Cache::get('mca-import:' . $state . ':done')) . ' / ' . number_format($p['total']) . '  ' . Cache::get('mca-import:' . $state . ':updated', $p['started_at'])
                    : 'not started'));
            }
            $this->info('Stored: ' . number_format(\Illuminate\Support\Facades\DB::table('mca_companies')->count()) . ' companies.');
            return self::SUCCESS;
        }
        if (!$mca->configured()) { $this->error('Set DATAGOV_API_KEY in .env first.'); return self::FAILURE; }
        foreach ($states as $state) {
            if ($this->option('now')) {
                (new ImportMcaCompaniesPage($state))->handle($mca);
                $this->info("{$state}: started; " . json_encode(Cache::get('mca-import:' . $state)) . '. The rest continues on the registry queue.');
            } else {
                ImportMcaCompaniesPage::dispatch($state)->onConnection('database')->onQueue('registry');
                $this->info("{$state}: queued.");
            }
        }
        return self::SUCCESS;
    }
}
