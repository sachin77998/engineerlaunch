<?php

namespace App\Http\Controllers;

use App\Models\CompanyFinancialStatement;
use App\Models\UsCompanyFinancial;
use App\Services\PremiumPlans;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Explore databases (Insights): India-listed companies, companies with financials, US-listed companies and directors,
 * as filterable tables. Without Premium a search shows its first 5 rows and what a plan unlocks.
 */
class InsightsExploreController extends Controller
{
    public const DATABASES = ['listed' => 'Listed in India', 'companies' => 'Companies', 'us' => 'US-listed companies', 'people' => 'Directors & KMP'];
    private const FREE_ROWS = 5;

    public function show(Request $request, string $db, PremiumPlans $plans)
    {
        abort_unless(isset(self::DATABASES[$db]), 404);
        $f = $request->validate(['q' => 'nullable|string|max:80', 'sector' => 'nullable|string|max:120', 'state' => 'nullable|string|max:80']);
        $premium = (bool) $request->user()?->hasActivePremium();
        $query = match ($db) {
            'listed' => $this->indian($f, true),
            'companies' => $this->indian($f, false),
            'us' => $this->us($f),
            'people' => $this->people($f),
        };
        $total = (clone $query)->getCountForPagination();
        $rows = $premium ? $query->paginate(25)->withQueryString() : $query->limit(self::FREE_ROWS)->get();
        $items = $premium ? $rows->getCollection() : $rows;
        $growth = match ($db) {
            'listed', 'companies' => $this->indianGrowth($items->pluck('id')->all()),
            'us' => $this->usGrowth($items->pluck('cik')->filter()->all()),
            default => [],
        };
        $sectors = Cache::remember('explore:sectors:' . $db, now()->addHours(6), fn () => $db === 'us'
            ? DB::table('us_listed_companies')->whereNotNull('sector')->distinct()->orderBy('sector')->pluck('sector')
            : DB::table('companies')->whereNotNull('industry')->where('industry', '!=', 'Other Industries')->distinct()->orderBy('industry')->pluck('industry'));
        $counts = Cache::remember('explore:counts', now()->addMinutes(30), fn () => [
            'listed' => DB::table('listed_companies')->whereNotNull('company_id')->count(), 'companies' => DB::table('companies')->where('is_active', true)->count(),
            'us' => DB::table('us_listed_companies')->count(), 'people' => DB::table('company_officers')->count(),
        ]);
        return view('insights.explore', ['db' => $db, 'databases' => self::DATABASES, 'f' => $f, 'rows' => $rows, 'items' => $items, 'total' => $total, 'premium' => $premium,
            'growth' => $growth, 'sectors' => $sectors, 'counts' => $counts, 'plans' => $plans->plans(), 'freeRows' => self::FREE_ROWS]);
    }

    /** Indian companies with their latest annual figures (standalone, else consolidated), largest revenue first. */
    private function indian(array $f, bool $listedOnly)
    {
        $latest = DB::table('company_financial_statements')->selectRaw('company_id, MAX(financial_year) as fy')->groupBy('company_id');
        return DB::table('companies as c')
            ->when($listedOnly, fn ($q) => $q->join('listed_companies as l', 'l.company_id', '=', 'c.id'), fn ($q) => $q->leftJoin('listed_companies as l', 'l.company_id', '=', 'c.id'))
            ->leftJoinSub($latest, 'ly', 'ly.company_id', '=', 'c.id')
            ->leftJoin('company_financial_statements as s', fn ($j) => $j->on('s.company_id', '=', 'c.id')->on('s.financial_year', '=', 'ly.fy')->where('s.is_consolidated', false))
            ->leftJoin('company_registry_details as r', 'r.company_id', '=', 'c.id')
            ->where('c.is_active', true)->where(fn ($q) => $q->where('c.country', 'India')->orWhereNull('c.country'))
            ->when(!$listedOnly, fn ($q) => $q->whereNotNull('ly.fy'))
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('c.name', 'like', '%' . addcslashes($t, '%_\\') . '%')->orWhere('l.symbol', strtoupper($t))))
            ->when($f['sector'] ?? null, fn ($q, $v) => $q->where('c.industry', $v))
            ->when($f['state'] ?? null, fn ($q, $v) => $q->where('c.registered_state', $v))
            ->orderByRaw('s.revenue IS NULL')->orderByDesc('s.revenue')->orderBy('c.name')
            ->select(['c.id', 'c.name', 'c.slug', 'c.website', 'c.logo_url', 'c.industry', 'c.sector', 'c.description', 'c.registered_state', 'c.headquarters', 'c.registry_cin',
                'l.symbol', 'ly.fy', 's.revenue', 's.ebitda', 's.profit_after_tax', 'r.city', 'r.incorporation_date', 'r.principal_activity', 'r.listing_status'])->distinct();
    }

    private function us(array $f)
    {
        $latest = DB::table('us_company_financials')->selectRaw('cik, MAX(fiscal_year) as fy')->groupBy('cik');
        return DB::table('us_listed_companies as u')->leftJoin('companies as c', 'c.id', '=', 'u.company_id')
            ->leftJoinSub($latest, 'ly', 'ly.cik', '=', 'u.cik')
            ->leftJoin('us_company_financials as s', fn ($j) => $j->on('s.cik', '=', 'u.cik')->on('s.fiscal_year', '=', 'ly.fy'))
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('u.name', 'like', '%' . addcslashes($t, '%_\\') . '%')->orWhere('u.ticker', strtoupper($t))))
            ->when($f['sector'] ?? null, fn ($q, $v) => $q->where('u.sector', $v))
            ->when($f['state'] ?? null, fn ($q, $v) => $q->where('u.country', $v))
            ->orderByRaw('u.market_cap IS NULL')->orderByDesc('u.market_cap')
            ->select(['u.id', 'u.ticker', 'u.cik', 'u.name', 'u.exchange', 'u.sector', 'u.industry', 'u.country', 'u.ipo_year', 'u.market_cap', 'u.last_price', 'u.change_pct',
                'c.slug', 'c.website', 'c.logo_url', 'ly.fy', 's.currency', 's.revenue', 's.operating_income', 's.net_income']);
    }

    private function people(array $f)
    {
        return DB::table('company_officers as o')->join('companies as c', 'c.id', '=', 'o.company_id')
            ->when($f['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('o.name', 'like', '%' . addcslashes($t, '%_\\') . '%')->orWhere('o.din', $t)->orWhere('c.name', 'like', '%' . addcslashes($t, '%_\\') . '%')))
            ->when($f['sector'] ?? null, fn ($q, $v) => $q->where('c.industry', $v))
            ->when($f['state'] ?? null, fn ($q, $v) => $q->where('c.registered_state', $v))
            ->orderByRaw('o.ceased_on IS NOT NULL')->orderBy('o.name')
            ->select(['o.id', 'o.name', 'o.designation', 'o.din', 'o.appointed_on', 'o.ceased_on', 'c.name as company', 'c.slug', 'c.industry', 'c.registered_state']);
    }

    /** Year-on-year change of revenue, EBITDA and profit for the companies on the page. */
    private function indianGrowth(array $ids): array
    {
        $out = [];
        CompanyFinancialStatement::whereIn('company_id', $ids)->where('is_consolidated', false)->orderByDesc('financial_year')->get(['company_id', 'financial_year', 'revenue', 'ebitda', 'profit_after_tax'])
            ->groupBy('company_id')->each(function ($rows, $id) use (&$out) {
                [$now, $prev] = [$rows->get(0), $rows->get(1)];
                foreach (['revenue' => 'revenue', 'ebitda' => 'ebitda', 'profit_after_tax' => 'profit_after_tax'] as $k => $col) {
                    $out[$id][$k] = $now && $prev && $prev->{$col} > 0 && $now->{$col} !== null ? ($now->{$col} / $prev->{$col} - 1) * 100 : null;
                }
            });
        return $out;
    }

    private function usGrowth(array $ciks): array
    {
        $out = [];
        UsCompanyFinancial::whereIn('cik', $ciks)->orderByDesc('fiscal_year')->get(['cik', 'fiscal_year', 'revenue', 'operating_income', 'net_income'])
            ->groupBy('cik')->each(function ($rows, $cik) use (&$out) {
                [$now, $prev] = [$rows->get(0), $rows->get(1)];
                foreach (['revenue', 'operating_income', 'net_income'] as $col) {
                    $out[$cik][$col] = $now && $prev && $prev->{$col} > 0 && $now->{$col} !== null ? ($now->{$col} / $prev->{$col} - 1) * 100 : null;
                }
            });
        return $out;
    }
}
