<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CreditRating;
use App\Models\CreditRatingSource;
use App\Services\Insights\CreditRatingSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncCreditRatings extends Command
{
    protected $signature = 'insights:ratings {--icra : Only ICRA daily rationales} {--care : Only CARE company lookups} {--limit=250 : Newest ICRA ids to check}
        {--backfill=0 : Older ICRA ids to read below the oldest stored} {--care-limit=120 : Companies looked up at CARE per run}
        {--company=* : Only these company ids (CARE lookup)} {--relink : Only link stored rationales to companies} {--reparse : Re-read stored rationales with the current parser} {--missing : With --reparse: only reports without funding events} {--batch=0 : With --reparse: at most this many reports} {--status : Show counts only}';
    protected $description = 'Fetch credit-rating rationales (ICRA daily releases, CARE company lookups) and store ratings, rated debt and agency financials';

    public function handle(CreditRatingSync $sync): int
    {
        $log = fn ($m) => $this->line($m);
        if ($this->option('status')) {
            $this->info('Rationales: ' . CreditRating::where('parse_status', 'parsed')->count() . ' (linked to companies: ' . CreditRating::whereNotNull('company_id')->count()
                . '); newest ICRA id: ' . (CreditRating::where('agency', 'ICRA')->max(DB::raw('CAST(source_key AS UNSIGNED)')) ?? '—')
                . '; CARE lookups: ' . CreditRatingSource::where('agency', 'CARE')->count() . ' (found: ' . CreditRatingSource::where('agency', 'CARE')->whereNotNull('agency_company_id')->count() . ')');
            return self::SUCCESS;
        }
        if ($this->option('relink')) { $this->info('Linked ' . $sync->relink() . ' rationales.'); return self::SUCCESS; }
        if ($this->option('reparse')) { $sync->reparse(($c = $this->option('company')) ? (int) $c[0] : null, $log, (int) $this->option('batch'), (bool) $this->option('missing')); return self::SUCCESS; }

        $only = $this->option('icra') ? 'icra' : ($this->option('care') || $this->option('company') ? 'care' : null);
        if ($only !== 'care') $sync->syncIcra((int) $this->option('limit'), (int) $this->option('backfill'), $log);
        if ($only !== 'icra') {
            $ids = array_map('intval', (array) $this->option('company'));
            $companies = $ids ? Company::whereIn('id', $ids)->get() : $this->careQueue((int) $this->option('care-limit'));
            $found = 0;
            foreach ($companies as $company) {
                $source = $sync->syncCare($company, $log);
                if ($source?->agency_company_id) $found++;
            }
            $this->line("CARE: looked up {$companies->count()} companies; found at CARE: {$found}.");
        }
        $this->info('Linked ' . $sync->relink() . ' stored rationales to companies.');
        return self::SUCCESS;
    }

    /** Companies due a CARE check (never, or over 30 days ago): ones with financials, registry records or page views first. */
    private function careQueue(int $limit)
    {
        $recent = CreditRatingSource::where('agency', 'CARE')->where('checked_at', '>=', now()->subDays(30))->pluck('company_id');
        $views = DB::table('company_page_views')->where('viewed_on', '>=', now()->subDays(30))->select('company_id', DB::raw('COUNT(*) as n'))->groupBy('company_id');
        return Company::query()->where('is_active', true)->whereNotIn('companies.id', $recent)
            ->leftJoinSub($views, 'v', 'v.company_id', '=', 'companies.id')
            ->select('companies.*')
            ->orderByRaw('COALESCE(v.n, 0) DESC')
            ->orderByRaw('EXISTS (SELECT 1 FROM company_financial_statements s WHERE s.company_id = companies.id) DESC')
            ->orderByRaw('EXISTS (SELECT 1 FROM company_registry_details r WHERE r.company_id = companies.id) DESC')
            ->orderBy('companies.id')
            ->limit($limit)->get();
    }
}
