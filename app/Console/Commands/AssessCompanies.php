<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Insights\CareAssessment;
use App\Services\Insights\FundingLifecycle;
use App\Services\Insights\Methodology;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Computes and stores credit assessments, yearly ratio values, funding events and borrowing capacity for every company
// with financials or ratings, then refreshes sector medians. Oldest results first, in batches.
class AssessCompanies extends Command
{
    protected $signature = 'insights:assess {--company=* : Only these company ids} {--limit=1500 : Companies per run} {--all : Ignore staleness}
        {--medians : Only recompute sector medians} {--seed-methodology : Copy config/care_methodology.php into the methodology tables} {--reset : With --seed-methodology, replace edited values}';
    protected $description = 'Store credit assessments, ratios, funding events and borrowing capacity';

    public function handle(CareAssessment $assessment, FundingLifecycle $lifecycle, Methodology $methodology): int
    {
        if ($this->option('seed-methodology')) {
            $n = $methodology->seed((bool) $this->option('reset'));
            // Other agencies' sector criteria (config/icra_methodology.php).
            $n += app(\App\Services\Insights\AgencyCriteria::class)->seed((bool) $this->option('reset'));
            // New or refreshed methodology rows: stored assessments are recomputed on view or by the nightly run.
            if ($n) DB::table('company_credit_assessments')->update(['computed_at' => null]);
            $this->info("Methodology tables seeded ($n rows written).");
            return self::SUCCESS;
        }
        if ($this->option('medians')) { $this->info('Sector medians stored: ' . $assessment->computeMedians()); return self::SUCCESS; }

        $ids = $this->option('company');
        $stale = (int) ($methodology->setting('overlays', [])['stale_days'] ?? 7);
        $pick = Company::query()
            ->when($ids, fn ($q) => $q->whereIn('companies.id', $ids), fn ($q) => $q->where(fn ($w) => $w
                ->whereIn('companies.id', DB::table('company_financial_statements')->select('company_id'))
                ->orWhereIn('companies.id', DB::table('credit_ratings')->whereNotNull('company_id')->select('company_id'))))
            ->when(!$ids && !$this->option('all'), fn ($q) => $q->whereNotIn('companies.id', DB::table('company_credit_assessments')->where('computed_at', '>', now()->subDays($stale))->select('company_id')))
            ->leftJoin('company_credit_assessments as cca', 'cca.company_id', '=', 'companies.id')->orderByRaw('cca.computed_at IS NOT NULL, cca.computed_at')
            ->limit((int) $this->option('limit'))->pluck('companies.id');
        $done = 0; $failed = 0;
        foreach ($pick->chunk(100) as $chunk) {
            foreach (Company::with(['registryDetail', 'financialStatements'])->whereIn('id', $chunk)->get() as $c) {
                try { $assessment->persist($c); $lifecycle->persist($c); $done++; }
                catch (\Throwable $e) { $failed++; report($e); $this->warn("{$c->name}: " . $e->getMessage()); }
            }
        }
        $this->info("Assessed $done companies" . ($failed ? ", $failed failed" : '') . '. Sector medians stored: ' . $assessment->computeMedians());
        return self::SUCCESS;
    }
}
