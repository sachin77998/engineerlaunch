<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Insights\ListedSectors;
use App\Services\SectorDirectory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FileListedSectors extends Command
{
    protected $signature = 'sectors:file-listed {--symbol=* : Only these NSE symbols} {--dry-run : Show decisions without saving}';
    protected $description = 'File every NSE-listed company under its sector card using the NSE industry and the CIN industry code';

    public function handle(ListedSectors $sectors): int
    {
        $industries = $sectors->industries();
        $this->info(count($industries) . ' NSE industries loaded.');
        $query = DB::table('listed_companies')->whereNotNull('company_id')->orderBy('symbol');
        if ($symbols = array_map('strtoupper', $this->option('symbol'))) $query->whereIn('symbol', $symbols);
        $changed = 0;
        foreach ($query->get(['symbol', 'name', 'cin', 'company_id']) as $listed) {
            $company = Company::find($listed->company_id);
            if (!$company) continue;
            $cin = $listed->cin ?: $company->registryDetail?->cin;
            $decision = $sectors->classify($listed->name, $industries[$listed->symbol] ?? null, $cin);
            if (!$decision) continue;
            if ($this->option('dry-run') || $this->output->isVerbose()) $this->line("{$listed->symbol} ({$listed->name}): " . implode(' / ', $decision));
            if (!$this->option('dry-run') && $sectors->file($company, $decision)) $changed++;
        }
        if (!$this->option('dry-run')) SectorDirectory::flush();
        $this->info("{$changed} companies newly filed under a sector.");
        return self::SUCCESS;
    }
}
