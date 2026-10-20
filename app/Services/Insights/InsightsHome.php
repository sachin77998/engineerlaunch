<?php

namespace App\Services\Insights;

use App\Services\Market\MarketSnapshot;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Data for the Insights dashboard: database snapshot, markets today, companies by sector and recently viewed pages. */
class InsightsHome
{
    public function __construct(private MarketSnapshot $markets) {}

    public function build(): array
    {
        return [
            'snapshot' => $this->snapshot(),
            'india' => rescue(fn () => ['indices' => $this->markets->indices(MarketSnapshot::INDIA)] + $this->markets->india(), ['indices' => []], false),
            'us' => rescue(fn () => $this->markets->indices(MarketSnapshot::US), [], false),
            'sectors' => $this->sectors(),
            'recent' => collect(session('insights.recent', []))->take(8),
        ];
    }

    private function snapshot(): array
    {
        return Cache::remember('insights-home:snapshot', now()->addMinutes(30), function () {
            $count = fn (string $table, ?callable $where = null) => rescue(fn () => ($where ? $where(DB::table($table)) : DB::table($table))->count(), 0, false);
            return [
                ['Companies', $count('companies'), 'India & global', '🏢', route('insights.explore', 'companies')],
                ['Registered (MCA)', $count('mca_companies'), 'With CIN', '🧾', route('insights.index') . '#directory'],
                ['Directors & KMP', $count('company_officers'), 'People', '👥', route('insights.explore', 'people')],
                ['Listed in India', $count('listed_companies'), 'NSE equities', '🇮🇳', route('insights.explore', 'listed')],
                ['US-listed', $count('us_listed_companies'), 'NYSE & Nasdaq', '🇺🇸', route('markets.us')],
                ['Screened financials', rescue(fn () => DB::table('company_financial_statements')->distinct()->count('company_id'), 0, false) + rescue(fn () => DB::table('us_company_financials')->distinct()->count('cik'), 0, false), 'Companies with results', '📊', route('insights.explore', 'listed')],
                ['Borrowings (charges)', $count('company_charges'), 'Secured loans', '🏦', route('insights.explore', 'companies')],
                ['Open jobs', $count('jobs', fn ($q) => $q->where('is_active', true)), 'At these companies', '💼', url('/jobs-posted-by-hr')],
            ];
        });
    }

    /** Companies per top-level sector (for the trends chart). */
    private function sectors(): array
    {
        return Cache::remember('insights-home:sectors', now()->addHours(6), fn () => rescue(fn () => DB::table('company_category_company as cc')
            ->join('company_categories as c', 'c.id', '=', 'cc.company_category_id')->whereNull('c.parent_id')->where('c.taxonomy', 'sector')
            ->where('c.name', '!=', 'Other Industries')->groupBy('c.name')->orderByRaw('COUNT(*) DESC')->limit(10)
            ->selectRaw('c.name, COUNT(*) as n')->pluck('n', 'name')->all(), [], false));
    }

    /** Remember a viewed company page in the session (most recent first). */
    public static function remember(\App\Models\Company $company, ?string $place): void
    {
        try {
            $recent = collect(session('insights.recent', []))->reject(fn ($r) => $r['slug'] === $company->slug)
                ->prepend(['slug' => $company->slug, 'name' => $company->name, 'place' => $place, 'logo' => $company->logo_url])->take(10)->values()->all();
            session(['insights.recent' => $recent]);
        } catch (Throwable) {
        }
    }
}
