<?php

namespace App\Console\Commands;

use App\Services\Funds\FundCreditQuality;
use App\Services\Funds\PortfolioImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportFundPortfolios extends Command
{
    protected $signature = 'mf:portfolios {--amc=* : Only these AMCs (partial name)} {--rescore : Re-score stored holdings without downloading}';
    protected $description = 'Fetch AMC monthly portfolio disclosures, store holdings and compute fund credit quality (CQR)';

    public function handle(PortfolioImporter $importer, FundCreditQuality $quality): int
    {
        $added = $importer->seedSources();
        if ($added) $this->info("Added $added portfolio source(s).");
        if ($this->option('rescore')) {
            $files = DB::table('mf_portfolio_files')->where('status', 'parsed')->orderBy('as_of')->pluck('id');
            $n = 0;
            foreach ($files as $id) $n += $quality->scoreFile($id);
            $this->info("Re-scored $n scheme-months from {$files->count()} files.");
            $this->icra(true);
            return self::SUCCESS;
        }
        $sources = DB::table('mf_portfolio_sources')->where('is_active', true)
            ->when($this->option('amc'), fn ($q, $amcs) => $q->where(fn ($w) => collect($amcs)->each(fn ($a) => $w->orWhere('amc', 'like', "%$a%"))))->get();
        foreach ($sources as $s) {
            $this->line("<info>{$s->amc}</info>");
            $r = $importer->run($s, fn ($m) => $this->line('  ' . $m));
            $this->line('  → ' . $r['status']);
        }
        $this->info('Schemes with credit quality: ' . DB::table('mf_credit_quality')->distinct()->count('scheme_key'));
        $this->icra(false);
        return self::SUCCESS;
    }

    /** ICRA method, month by month (oldest first) so each month's history is in place; $all re-rates every month. */
    private function icra(bool $all): void
    {
        $icra = app(\App\Services\Funds\IcraFundQuality::class);
        $months = DB::table('mf_portfolio_holdings')->distinct()->orderBy('as_of')->pluck('as_of');
        if (!$all) {
            $done = DB::table('mf_icra_credit_quality')->distinct()->pluck('as_of')->map(fn ($d) => (string) $d)->all();
            $months = $months->filter(fn ($d) => !in_array((string) $d, $done, true) || $d === $months->last());
        } else {
            DB::table('mf_icra_credit_quality')->delete();
        }
        $n = 0;
        foreach ($months as $d) $n += $icra->scoreMonth((string) $d);
        $this->info("ICRA method: $n scheme-months rated.");
    }
}
