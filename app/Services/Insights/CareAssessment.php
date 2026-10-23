<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CreditRating;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sector-specific credit assessment following CareEdge methodologies (config/care_methodology.php):
 *  - picks the sector methodology from industry, NIC code and name,
 *  - computes the "Financial Ratios – Non-Financial / Financial Sector" ratios from audited statements
 *    (CCDs treated as quasi-equity, OCDs as debt), with sector-specific bands and peer medians,
 *  - scores business / financial / liquidity / management pillars into an indicative long-term band,
 *    maps it to the short-term scale using the liquidity indicator,
 *  - applies policy overlays: default recognition and curing period, issuer non-cooperation (INC > 6 months
 *    caps at non-investment grade), government linkage and group support notching, credit enhancement (CE/SO),
 *    provisional ratings, withdrawal reasons, outlook vs rating watch, and information adequacy.
 */
class CareAssessment
{
    private const LIQUIDITY = ['Superior', 'Strong', 'Adequate', 'Stretched', 'Poor'];

    public function __construct(private CreditOutlookModel $outlook, private Methodology $m) {}

    public function build(Company $company, bool $full = true): ?array
    {
        $company->loadMissing(['registryDetail']);
        $sectorKey = $this->sector($company);
        $sector = $this->m->sector($sectorKey);
        $financialSector = !empty($sector['financial_ratios']);
        $ratings = $this->ratings($company);
        $statements = $this->statements($company);
        if ($statements->isEmpty() && $ratings->isEmpty()) return null;

        $basis = $this->basis($company, $statements);
        $years = $statements->where('is_consolidated', $basis === 'consolidated')->sortBy('financial_year')->values();
        if ($years->isEmpty()) $years = $statements->sortBy('financial_year')->groupBy('financial_year')->map->first()->values();
        $funding = $ratings->flatMap(fn ($r) => collect((array) $r->funding_events)->map(fn ($e) => $e + ['date' => $r->published_on]));

        $ratios = $years->isNotEmpty() ? $this->ratios($years, $funding, $financialSector) : [];
        $ratios['promoter_holding'] = $this->promoterHolding($company);
        $ratios['rpt_revenue'] = $this->relatedPartyShare($company, $years->last());
        $info = $this->information($company, $years, $ratings, $funding);

        $keys = array_values(array_diff($sector['ratios'] ?? $this->m->sector('manufacturing')['ratios'] ?? [], $sector['skip'] ?? []));
        $peers = $full ? $this->peerMedians($sectorKey) : [];
        $rows = [];
        foreach ($keys as $k) {
            $def = $this->m->ratio($k);
            if (!$def) continue;
            $band = $sector['override'][$k] ?? $def['band'];
            $v = $ratios[$k] ?? null;
            $rows[$k] = ['key' => $k, 'label' => $def['label'], 'value' => $v, 'band' => $band, 'dir' => $def['dir'], 'pillar' => $def['pillar'],
                'score' => $v === null ? null : $this->bandScore($v, $band, $def['dir']), 'peer' => $peers[$k] ?? null];
        }
        $weights = $sector['weights'] ?? $this->m->setting('default_weights', ['business' => 30, 'financial' => 35, 'liquidity' => 20, 'management' => 15]);
        $pillars = [];
        foreach ($weights as $p => $w) {
            $scored = collect($rows)->where('pillar', $p)->whereNotNull('score');
            $pillars[$p] = ['weight' => $w, 'score' => $scored->isEmpty() ? null : round($scored->avg('score') / 5 * 100, 1), 'count' => $scored->count()];
        }
        $usable = collect($pillars)->filter(fn ($p) => $p['score'] !== null && $p['weight'] > 0);
        // An indicative band needs audited figures: no financial pillar, no band.
        $score = $full && ($pillars['financial']['count'] ?? 0) >= ($financialSector ? 1 : 2) && $usable->sum('weight') > 0 ? round($usable->sum(fn ($p) => $p['score'] * $p['weight']) / $usable->sum('weight'), 1) : null;

        $liquidity = $this->liquidity($ratios, $funding, $ratings->last());
        $latest = $ratings->where('withdrawn', false)->last();
        $standalone = $score !== null ? $this->band($score) : null;
        [$final, $adjustments] = $standalone ? $this->overlays($standalone, $company, $ratings, $funding, $info) : [null, []];
        $checks = $this->checks($sector['checks'] ?? [], $years, $ratios, $funding, $company);
        return [
            'sector_key' => $sectorKey, 'sector' => $sector['name'], 'criteria' => $sector['criteria'], 'notes' => $sector['notes'] ?? [],
            'basis' => $basis, 'full' => $full, 'year' => $years->last()?->financial_year,
            'rows' => $full ? $rows : [], 'pillars' => $full ? $pillars : [], 'score' => $score,
            'standalone' => $standalone, 'indicative' => $final, 'adjustments' => $adjustments,
            'short_term' => $final ? $this->shortTerm($final, (string) $liquidity['level']) : null,
            'liquidity' => $liquidity, 'checks' => $full ? $checks : [],
            'agency' => $latest ? ['agency' => $latest->agency, 'rating' => $latest->rating_long, 'score' => $latest->score, 'date' => $latest->published_on,
                'gap' => $final && $latest->score ? RatingRationaleParser::score($final) - $latest->score : null] : null,
            'policy' => $this->policy($ratings),
            'outlook' => $this->outlookView($ratings, $years, $funding),
            'information' => $info,
        ];
    }

    public function sector(Company $company): string
    {
        $industry = (string) $company->industry;
        $name = (string) $company->name;
        $nic = (string) ($company->registryDetail?->nic_code ?? '');
        if (preg_match('/\bbank\b/i', $name) && !preg_match('/finance|investment/i', $name)) return 'bank';
        // Stated industry first, then the company name; registry NIC codes are often stale, so they only decide generic industries.
        foreach (['industry', 'name', 'nic'] as $by) {
            if ($by === 'nic' && ($nic === '' || ($industry !== '' && !preg_match('/^other|^manufacturing$|^industrial/i', $industry)))) break;
            foreach ($this->m->sectors() as $key => $def) {
                if ($key === 'manufacturing') continue;
                $m = $def['match'] ?? [];
                if ($by === 'industry' && !empty($m['industry']) && preg_match($m['industry'], $industry)) return $key;
                if ($by === 'name' && !empty($m['name']) && preg_match($m['name'], $name)) return $key;
                if ($by === 'nic' && collect($m['nic'] ?? [])->contains(fn ($p) => str_starts_with($nic, $p))) return $key;
            }
        }
        return 'manufacturing';
    }

    /** Consolidated when the company reports subsidiaries and consolidated accounts exist (consolidation criteria). */
    private function basis(Company $company, Collection $statements): string
    {
        $hasConsolidated = $statements->where('is_consolidated', true)->isNotEmpty();
        $subsidiaries = rescue(fn () => $company->relatedParties()->where('relationship', 'like', '%subsidiar%')->exists(), false, false);
        return $hasConsolidated && ($subsidiaries || $statements->where('is_consolidated', false)->isEmpty()) ? 'consolidated' : 'standalone';
    }

    private function ratios(Collection $years, Collection $funding, bool $financialSector): array
    {
        $t = $years->last();
        $p = $years->count() > 1 ? $years[$years->count() - 2] : null;
        $n = fn ($v) => $v === null ? null : (float) $v;
        $div = fn ($a, $b) => $a === null || $b === null || abs((float) $b) < 1e-9 ? null : (float) $a / (float) $b;
        $avg = fn ($f) => $p && $p->$f !== null && $t->$f !== null ? ((float) $t->$f + (float) $p->$f) / 2 : $n($t->$f);
        $toi = $n($t->revenue);
        $pbildt = $n($t->ebitda) ?? ($toi !== null && $t->total_expenses !== null ? $toi - ((float) $t->total_expenses - (float) $t->finance_cost - (float) $t->depreciation) : null);
        $interest = $n($t->finance_cost);
        $gca = $t->profit_after_tax !== null ? (float) $t->profit_after_tax + (float) $t->depreciation : null;
        $ltDebt = $n($t->long_term_borrowings);
        $stDebt = $n($t->short_term_borrowings);
        $debt = $n($t->total_debt) ?? ($ltDebt !== null || $stDebt !== null ? (float) $ltDebt + (float) $stDebt : null);
        $tnw = $n($t->net_worth) ?? ($t->share_capital !== null ? (float) $t->share_capital + (float) $t->reserves : null);
        // CCDs raised in the latest year are quasi-equity: move them out of debt and into net worth.
        $ccd = $funding->filter(fn ($e) => ($e['type'] ?? '') === 'inflow' && ($e['instrument'] ?? '') === 'ccd' && !empty($e['amount_crore']) && $t->period_end && Carbon::parse($e['date'])->between(Carbon::parse($t->period_end)->subYear(), Carbon::parse($t->period_end)->addMonths(6)))->sum('amount_crore') * 1e7;
        if ($ccd > 0 && $debt !== null) { $shift = min($ccd, $debt); $debt -= $shift; $tnw = (float) $tnw + $shift; }
        $tol = $t->total_assets !== null && $tnw !== null ? (float) $t->total_assets - $tnw : null;
        $costOfSales = $toi !== null && $pbildt !== null ? $toi - $pbildt : null;
        $repayment = $funding->where('type', 'obligation')->sortByDesc('date')->first()['amount_crore'] ?? null;
        $repayment = $repayment !== null ? $repayment * 1e7 : ($ltDebt !== null ? $ltDebt / 5 : null);
        $capitalEmployed = $tnw !== null && $debt !== null ? $tnw + $debt : null;
        $pbit = $pbildt !== null ? $pbildt - (float) $t->depreciation + (float) $t->other_income : null;
        $margins = $years->take(-3)->map(fn ($y) => $y->revenue > 0 && $y->ebitda !== null ? $y->ebitda / $y->revenue * 100 : null)->filter(fn ($v) => $v !== null);
        $r = [
            'toi_crore' => $toi !== null ? round($toi / 1e7, 2) : null,
            'growth_toi' => $p && $p->revenue > 0 && $toi !== null ? round(($toi / $p->revenue - 1) * 100, 2) : null,
            'pbildt_margin' => $this->pct($div($pbildt, $toi)),
            'pat_margin' => $this->pct($div($t->profit_after_tax, $toi)),
            'margin_stability' => $margins->count() >= 2 ? round($margins->max() - $margins->min(), 2) : null,
            'roce' => $this->pct($div($pbit, $p && $p->net_worth !== null ? ($capitalEmployed + (float) $p->net_worth + (float) ($p->total_debt ?? ((float) $p->long_term_borrowings + (float) $p->short_term_borrowings))) / 2 : $capitalEmployed)),
            'ronw' => $this->pct($div($t->profit_after_tax, $avg('net_worth'))),
            'lt_debt_equity' => $this->x($div($ltDebt, $tnw)),
            'gearing' => $this->x($div($debt, $tnw)),
            'tol_tnw' => $this->x($div($tol, $tnw)),
            'debt_pbildt' => $this->x($div($debt, $pbildt)),
            'debt_gca' => $this->x($div($debt, $gca)),
            'icr' => $this->x($div($pbildt, $interest)),
            'dscr' => $this->x($div($gca !== null ? $gca + (float) $interest : null, $repayment !== null ? $repayment + (float) $interest : null)),
            'current_ratio' => $this->x($div($t->current_assets, $t->current_liabilities)),
            'quick_ratio' => $this->x($div($t->current_assets !== null ? (float) $t->current_assets - (float) $t->inventories : null, $t->current_liabilities)),
            'cash_st_debt' => $this->x($div($t->cash_and_bank, $stDebt !== null && $stDebt > 0 ? $stDebt : null)),
            'collection_days' => $this->days($div($avg('trade_receivables'), $toi)),
            'inventory_days' => $this->days($div($avg('inventories'), $costOfSales)),
            'creditor_days' => $this->days($div($avg('trade_payables'), $costOfSales)),
            'fixed_asset_turnover' => $this->x($div($toi, $avg('fixed_assets'))),
            'nwc_ca' => $this->pct($div($t->current_assets !== null && $t->current_liabilities !== null ? (float) $t->current_assets - (float) $t->current_liabilities : null, $t->current_assets)),
        ];
        $r['wc_cycle'] = $r['collection_days'] !== null && $r['inventory_days'] !== null ? round($r['collection_days'] + $r['inventory_days'] - (float) $r['creditor_days'], 0) : null;
        if ($financialSector) {
            $r['rota'] = $this->pct($div($t->profit_after_tax, $avg('total_assets')));
            $r['fin_gearing'] = $r['gearing'];
            $income = $n($t->total_income) ?? $toi;
            $opex = $t->employee_cost !== null || $t->other_expenses !== null ? (float) $t->employee_cost + (float) $t->other_expenses : null;
            $r['cost_to_income'] = $this->pct($div($opex, $income !== null && $interest !== null ? $income - $interest : null));
            $r['cost_of_borrowing'] = ($cb = $this->pct($div($interest, $p ? ($debt + (float) ($p->total_debt ?? ((float) $p->long_term_borrowings + (float) $p->short_term_borrowings))) / 2 : $debt))) !== null && $cb <= 20 ? $cb : null;
        }
        return $r;
    }

    private function pct(?float $v): ?float { return $v === null ? null : round($v * 100, 2); }
    private function x(?float $v): ?float { return $v === null ? null : round($v, 2); }
    private function days(?float $v): ?float { return $v === null ? null : round($v * 365, 0); }

    private function bandScore(float $v, array $band, string $dir): int
    {
        foreach ($band as $i => $limit) if ($dir === 'up' ? $v >= $limit : $v <= $limit) return 5 - $i;
        return 1;
    }

    private function band(float $score): string
    {
        $bands = $this->m->setting('score_bands', []);
        krsort($bands, SORT_NUMERIC);
        foreach ($bands as $min => $rating) if ($score >= (float) $min) return $rating;
        return 'C';
    }

    private function shift(string $rating, int $notches, ?string $cap = null): string
    {
        $scale = RatingRationaleParser::LONG_SCALE;
        $i = max(0, min(count($scale) - 1, array_search($rating, $scale, true) - $notches));
        if ($cap !== null) $i = max($i, array_search($cap, $scale, true));
        return $scale[$i];
    }

    /** Notching and caps from the policy criteria. Each adjustment is recorded with its reason. */
    private function overlays(string $rating, Company $company, Collection $ratings, Collection $funding, array $info): array
    {
        $adj = [];
        $o = $this->m->setting('overlays', []) + ['gov_notch_51' => 1, 'gov_notch_75' => 2, 'group_support_notch' => 1, 'group_support_years' => 3, 'inc_cap_months' => 6, 'inc_cap' => 'BB+', 'cure_non_ig_days' => 90, 'cure_ig_days' => 365, 'post_default_cap' => 'BB+'];
        $gov = $this->governmentHolding($company);
        if ($gov !== null && $gov >= 51) { $to = $this->shift($rating, (int) ($gov >= 75 ? $o['gov_notch_75'] : $o['gov_notch_51'])); if ($to !== $rating) $adj[] = ['from' => $rating, 'to' => $to, 'why' => 'Government holds ' . round($gov, 1) . '% (notching for government linkage)']; $rating = $to; }
        $support = $funding->filter(fn ($e) => ($e['type'] ?? '') === 'inflow' && preg_match('/promoter|parent|group|holding|limited|ltd/i', (string) ($e['from'] ?? '')) && Carbon::parse($e['date'])->gt(now()->subYears((int) $o['group_support_years'])));
        if ($support->isNotEmpty()) { $to = $this->shift($rating, (int) $o['group_support_notch']); if ($to !== $rating) $adj[] = ['from' => $rating, 'to' => $to, 'why' => 'Demonstrated funding support from ' . $support->first()['from'] . ' (₹' . round($support->sum('amount_crore')) . ' cr)']; $rating = $to; }
        $policy = $this->policy($ratings);
        if ($policy['default']['in_default']) { $adj[] = ['from' => $rating, 'to' => 'D', 'why' => 'Latest agency rating is in default']; return ['D', $adj]; }
        if ($policy['default']['days_since_cure'] !== null) {
            $cap = $policy['default']['days_since_cure'] < $o['cure_non_ig_days'] ? 'C' : ($policy['default']['days_since_cure'] < $o['cure_ig_days'] ? $o['post_default_cap'] : null);
            if ($cap && $this->shift($rating, 0, $cap) !== $rating) { $adj[] = ['from' => $rating, 'to' => $cap, 'why' => 'Curing period: ' . $policy['default']['days_since_cure'] . ' days since default was cured (' . $o['cure_non_ig_days'] . ' days for non-investment grade, ' . $o['cure_ig_days'] . ' for investment grade)']; $rating = $cap; }
        }
        if ($policy['inc']['months'] !== null && $policy['inc']['months'] >= $o['inc_cap_months'] && $this->shift($rating, 0, $o['inc_cap']) !== $rating) { $adj[] = ['from' => $rating, 'to' => $o['inc_cap'], 'why' => 'Issuer not cooperating for ' . $policy['inc']['months'] . ' months (investment grade not retained beyond ' . $o['inc_cap_months'] . ' months)']; $rating = $o['inc_cap']; }
        return [$rating, $adj];
    }

    private function shortTerm(string $long, string $liquidity): string
    {
        $map = $this->m->setting('short_term_map', [])[$long] ?? ['A4', 'A4'];
        return $map[in_array($liquidity, ['Superior', 'Strong'], true) ? 1 : 0];
    }

    /** Liquidity indicator (Superior/Strong, Adequate, Stretched, Poor) from cash, current ratio, limit utilisation and agency view. */
    private function liquidity(array $r, Collection $funding, ?CreditRating $latest): array
    {
        $points = 0; $why = [];
        if (($c = $r['cash_st_debt'] ?? null) !== null) { $points += $c >= 1 ? 2 : ($c >= 0.4 ? 1 : -1); $why[] = 'Cash covers ' . $c . '× short-term debt'; }
        if (($c = $r['current_ratio'] ?? null) !== null) { $points += $c >= 1.5 ? 1 : ($c < 1 ? -1 : 0); $why[] = 'Current ratio ' . $c . '×'; }
        if (($c = $r['icr'] ?? null) !== null) { $points += $c >= 5 ? 1 : ($c < 1.5 ? -1 : 0); }
        $util = $funding->where('type', 'utilisation')->sortByDesc('date')->first()['percent'] ?? null;
        if ($util !== null) { $points += $util <= 60 ? 1 : ($util >= 90 ? -1 : 0); $why[] = 'Working-capital limits ' . $util . '% utilised'; }
        $inputs = count($why) + (($r['icr'] ?? null) !== null ? 1 : 0);
        $level = $inputs === 0 ? null : ($points >= 4 ? 'Strong' : ($points >= 1 ? 'Adequate' : ($points >= -1 ? 'Stretched' : 'Poor')));
        if ($latest?->liquidity) $why[] = $latest->agency . ' view: ' . $latest->liquidity;
        return ['level' => $level, 'agency' => $latest?->liquidity, 'why' => $why];
    }

    /** Default, curing, non-cooperation, credit enhancement, provisional and withdrawal status from the rating history. */
    public function policy(Collection $ratings): array
    {
        $sorted = $ratings->sortBy('published_on')->values();
        $last = $sorted->last();
        $lastD = $sorted->filter(fn ($r) => $r->rating_long && preg_match('/(?<![A-Z])D(?![A-Z+-])/', $r->rating_long) && !str_contains($r->rating_long, 'AA'))->last();
        $cured = $lastD ? $sorted->first(fn ($r) => $r->published_on->gt($lastD->published_on) && $r->score && $r->score > 1) : null;
        $incStart = null;
        foreach ($sorted->reverse() as $r) { if ($r->non_cooperating) $incStart = $r->published_on; else break; }
        $ce = $sorted->filter(fn ($r) => preg_match('/\((CE|SO)\)/', (string) $r->rating_long . collect((array) $r->instruments)->pluck('rating')->implode(' ')))->last();
        $prov = $sorted->filter(fn ($r) => stripos((string) $r->rating_long . collect((array) $r->instruments)->pluck('rating')->implode(' '), 'provisional') !== false)->last();
        $withdrawn = $sorted->where('withdrawn', true)->last();
        $wText = strtolower(implode(' ', array_filter([$withdrawn?->rationale, $withdrawn?->about, $withdrawn?->title])));
        $reason = !$withdrawn ? null : match (true) {
            (bool) preg_match('/notice of withdrawal/', $wText) => 'Placed on notice of withdrawal, then withdrawn',
            (bool) preg_match('/not (been )?(placed|availed|issued|raised)|no amount (is )?outstanding|not used for mobilising/', $wText) => 'Proposed debt rated but not raised',
            (bool) preg_match('/merg|amalgamat|liquidat/', $wText) => 'Merger, amalgamation or liquidation',
            (bool) preg_match('/transfer/', $wText) => 'Facility transferred to another entity',
            (bool) preg_match('/redeem|redemption|repaid|repayment|closed|extinguish/', $wText) => 'Rated debt fully repaid / redeemed',
            (bool) preg_match('/no objection|noc|request/', $wText) => 'Withdrawn at company request with lender NOCs',
            default => 'Withdrawn (reason not stated in report)',
        };
        return [
            'default' => ['in_default' => (bool) ($lastD && $last && $last->is($lastD)), 'last_default' => $lastD?->published_on, 'cured_on' => $cured?->published_on,
                'days_since_cure' => $cured ? (int) $cured->published_on->diffInDays(now()) : null],
            'inc' => ['active' => (bool) $last?->non_cooperating, 'since' => $incStart, 'months' => $incStart && $last?->non_cooperating ? (int) $incStart->diffInMonths(now()) : null],
            'credit_enhanced' => $ce ? ['rating' => $ce->rating_long, 'agency' => $ce->agency, 'date' => $ce->published_on, 'type' => str_contains((string) $ce->rating_long, 'SO') ? 'Structured obligation (internal)' : 'Credit enhancement (external support)'] : null,
            'provisional' => $prov ? ['rating' => $prov->rating_long, 'date' => $prov->published_on, 'expires' => $prov->published_on->copy()->addMonths(18)] : null,
            'withdrawal' => $withdrawn ? ['date' => $withdrawn->published_on, 'agency' => $withdrawn->agency, 'reason' => $reason] : null,
        ];
    }

    /** Outlook (medium-term direction) versus rating watch (event pending). */
    private function outlookView(Collection $ratings, Collection $years, Collection $funding): array
    {
        $latest = $ratings->where('withdrawn', false)->last();
        $event = $ratings->filter(fn ($r) => $r->published_on->gt(now()->subMonths(9)) && preg_match('/merger|amalgamation|demerger|acquisition|scheme of arrangement/i', (string) $r->rationale))->last();
        $trend = 0;
        $tail = $years->take(-3)->values();
        if ($tail->count() >= 2) {
            $a = $tail->first(); $b = $tail->last();
            $mA = $a->revenue > 0 ? $a->ebitda / $a->revenue : null; $mB = $b->revenue > 0 ? $b->ebitda / $b->revenue : null;
            if ($mA !== null && $mB !== null) $trend += $mB - $mA > 0.02 ? 1 : ($mA - $mB > 0.02 ? -1 : 0);
            $gA = $a->net_worth > 0 ? ((float) $a->total_debt ?: (float) $a->long_term_borrowings + (float) $a->short_term_borrowings) / $a->net_worth : null;
            $gB = $b->net_worth > 0 ? ((float) $b->total_debt ?: (float) $b->long_term_borrowings + (float) $b->short_term_borrowings) / $b->net_worth : null;
            if ($gA !== null && $gB !== null) $trend += $gA - $gB > 0.2 ? 1 : ($gB - $gA > 0.2 ? -1 : 0);
        }
        $model = $latest ? $this->outlook->predict($latest, $ratings->filter(fn ($r) => $r->agency === $latest->agency && $r->published_on->lt($latest->published_on))->last()) : null;
        if ($model) $trend += $model['probability'] >= 0.85 ? 1 : ($model['probability'] < 0.5 ? -1 : 0);
        $direction = $trend >= 2 ? 'Positive' : ($trend <= -2 ? 'Negative' : 'Stable');
        return ['direction' => $direction, 'agency' => $latest?->outlook, 'probability' => $model['probability'] ?? null,
            'watch' => $event ? ['type' => 'Developing', 'date' => $event->published_on, 'agency' => $event->agency] : null];
    }

    /** Minimum information requirement (Information Adequacy Risk criteria annexure), checked against what is on file. */
    private function information(Company $company, Collection $years, Collection $ratings, Collection $funding): array
    {
        $last = $years->last();
        $items = [
            'Audited financial statements (last 18 months)' => $last && $last->period_end && Carbon::parse($last->period_end)->gt(now()->subMonths(18)),
            'Annual results history (3+ years)' => $years->count() >= 3,
            'Liquidity position' => $funding->whereIn('type', ['liquidity', 'utilisation'])->isNotEmpty() || ($last && $last->cash_and_bank !== null),
            'Debt obligations and repayment schedule' => $funding->where('type', 'obligation')->isNotEmpty() || ($last && $last->long_term_borrowings !== null),
            'Capex / investment plans' => $funding->where('type', 'capex')->isNotEmpty(),
            'Lender details' => rescue(fn () => $company->charges()->exists(), false, false) || $ratings->contains(fn ($r) => !empty($r->instruments)),
            'Auditor details' => rescue(fn () => $company->auditors()->exists(), false, false),
            'Shareholding pattern' => rescue(fn () => $company->shareholdings()->exists(), false, false),
            'Recent rating review (last 15 months)' => $ratings->contains(fn ($r) => $r->published_on->gt(now()->subMonths(15))),
        ];
        $have = count(array_filter($items));
        return ['items' => $items, 'score' => (int) round($have / count($items) * 100), 'missing' => array_keys(array_filter($items, fn ($v) => !$v))];
    }

    /** Sector-specific checks named in the methodology papers. */
    private function checks(array $keys, Collection $years, array $r, Collection $funding, Company $company): array
    {
        $out = [];
        $last = $years->last();
        foreach ($keys as $k) {
            $res = match ($k) {
                'capex_vs_networth' => ($cx = $funding->where('type', 'capex')->sortByDesc('date')->first()) && $last && $last->net_worth > 0
                    ? ['Project size vs net worth', round($cx['amount_crore'] * 1e7 / $last->net_worth * 100) . '% of net worth' . (!empty($cx['funding_mix']) ? ', debt:equity ' . $cx['funding_mix'] : ''), $cx['amount_crore'] * 1e7 / $last->net_worth > 0.5 ? 'risk' : 'ok'] : null,
                'commodity_margin_volatility' => ($r['margin_stability'] ?? null) !== null ? ['Margin volatility (raw material / price risk)', $r['margin_stability'] . ' pp swing over 3 years', $r['margin_stability'] > 5 ? 'risk' : 'ok'] : null,
                'inventory_build' => ($i = $this->trend($years, fn ($y) => $y->revenue > 0 && $y->inventories !== null ? $y->inventories / $y->revenue * 365 : null)) ? ['Inventory holding trend', round($i[0]) . ' → ' . round($i[1]) . ' days', $i[1] - $i[0] > 15 ? 'risk' : 'ok'] : null,
                'receivable_stretch' => ($i = $this->trend($years, fn ($y) => $y->revenue > 0 && $y->trade_receivables !== null ? $y->trade_receivables / $y->revenue * 365 : null)) ? ['Receivable days trend', round($i[0]) . ' → ' . round($i[1]) . ' days', $i[1] - $i[0] > 15 ? 'risk' : 'ok'] : null,
                'negative_cfo_proxy' => $last && $years->count() > 1 ? (function () use ($years, $last) {
                    $prev = $years[$years->count() - 2];
                    $wc = ((float) $last->trade_receivables + (float) $last->inventories - (float) $last->trade_payables) - ((float) $prev->trade_receivables + (float) $prev->inventories - (float) $prev->trade_payables);
                    $cfo = (float) $last->profit_after_tax + (float) $last->depreciation - $wc;
                    return ['Cash flow from operations (approx.)', '₹' . ($cfo < 0 ? '-' : '') . CompanyInsights::indian(abs($cfo) / 1e7, 1) . ' cr after ₹' . ($wc < 0 ? '-' : '') . CompanyInsights::indian(abs($wc) / 1e7, 1) . ' cr working-capital change', $cfo < 0 ? 'risk' : 'ok'];
                })() : null,
                'forex_exposure' => ($x = $funding->first(fn ($e) => preg_match('/export|import|forex|foreign currency|hedg/i', (string) ($e['evidence'] ?? '')))) ? ['Foreign exchange exposure', 'Mentioned in rating report', 'watch'] : null,
                'seasonality' => ['Seasonality', 'Revenue skewed to part of the year in this sector; liquidity cushion matters', 'watch'],
                'group_support' => ($s = $funding->first(fn ($e) => ($e['type'] ?? '') === 'inflow' && !empty($e['from']))) ? ['Group / promoter support', '₹' . ($s['amount_crore'] ?? '?') . ' cr from ' . $s['from'], 'ok'] : null,
                'ice_obsolescence' => preg_match('/piston|engine|exhaust|fuel|transmission|gear|clutch|radiator/i', (string) $company->name . ' ' . rescue(fn () => $company->products()->pluck('description')->implode(' '), '', false))
                    ? ['EV transition exposure', 'Products tied to combustion engines / transmissions', 'risk'] : ['EV transition exposure', 'No engine-specific products found', 'ok'],
                'keyman' => ['Key-person / doctor-promoter risk', ($this->promoterHolding($company) ?? 0) >= 50 ? 'Promoter-controlled' : 'Professionally held', 'watch'],
                'related_party_heavy' => ($r['rpt_revenue'] ?? null) !== null ? ['Related-party dealings', $r['rpt_revenue'] . '% of revenue', $r['rpt_revenue'] > 20 ? 'risk' : 'ok'] : null,
                default => null,
            };
            if ($res) $out[] = ['label' => $res[0], 'value' => $res[1], 'status' => $res[2]];
        }
        return $out;
    }

    private function trend(Collection $years, callable $f): ?array
    {
        $v = $years->take(-3)->map($f)->filter(fn ($x) => $x !== null)->values();
        return $v->count() >= 2 ? [$v->first(), $v->last()] : null;
    }

    private function promoterHolding(Company $company): ?float
    {
        return rescue(function () use ($company) {
            $fy = $company->shareholdings()->max('financial_year');
            $v = $fy ? $company->shareholdings()->where('financial_year', $fy)->where('category', 'like', 'Promoter%')->sum('percentage') : null;
            // No promoter (widely held) is not scored; fragmented holding needs judgement rather than a penalty.
            return $v ? round((float) $v, 2) : null;
        }, null, false);
    }

    private function governmentHolding(Company $company): ?float
    {
        return rescue(function () use ($company) {
            $fy = $company->shareholdings()->max('financial_year');
            $v = $fy ? $company->shareholdings()->where('financial_year', $fy)->where(fn ($q) => $q->where('category', 'like', '%Government%')->orWhere('category', 'like', '%President of India%'))->sum('percentage') : 0;
            if (!$v && preg_match('/public sector|psu|government/i', (string) $company->industry)) return 51.0;
            return $v ? (float) $v : null;
        }, null, false);
    }

    private function relatedPartyShare(Company $company, $statement): ?float
    {
        if (!$statement || !$statement->revenue) return null;
        return rescue(function () use ($company, $statement) {
            $sum = $company->relatedParties()->where('financial_year', $statement->financial_year)->sum('amount');
            return $sum ? round($sum / $statement->revenue * 100, 2) : null;
        }, null, false);
    }

    /** Sector medians stored by insights:assess (sector_ratio_medians). */
    private function peerMedians(string $sectorKey): array
    {
        return rescue(fn () => DB::table('sector_ratio_medians')->where('sector_key', $sectorKey)->pluck('median', 'ratio_key')->map(fn ($v) => round((float) $v, 2))->all(), [], false);
    }

    /** Recomputes the median of every ratio per sector from company_ratio_values (latest year per company). */
    public function computeMedians(): int
    {
        $latest = DB::table('company_ratio_values')->select('company_id', DB::raw('MAX(financial_year) fy'))->groupBy('company_id');
        $rows = DB::table('company_ratio_values as v')->joinSub($latest, 'l', fn ($j) => $j->on('l.company_id', '=', 'v.company_id')->on('l.fy', '=', 'v.financial_year'))
            ->join('company_credit_assessments as a', 'a.company_id', '=', 'v.company_id')->whereNotNull('v.value')
            ->get(['a.sector_key', 'v.ratio_key', 'v.value']);
        $now = now(); $n = 0;
        DB::table('sector_ratio_medians')->delete();
        foreach ($rows->groupBy(fn ($r) => $r->sector_key . '|' . $r->ratio_key) as $key => $g) {
            if ($g->count() < 5) continue;
            [$sector, $ratio] = explode('|', $key);
            $v = $g->pluck('value')->map(fn ($x) => (float) $x)->sort()->values();
            $c = $v->count();
            DB::table('sector_ratio_medians')->insert(['sector_key' => $sector, 'ratio_key' => $ratio, 'median' => $c % 2 ? $v[intdiv($c, 2)] : ($v[$c / 2 - 1] + $v[$c / 2]) / 2,
                'sample' => $c, 'computed_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
            $n++;
        }
        return $n;
    }

    /** Builds the assessment and stores it, with every year's ratio values. */
    public function persist(Company $company): ?array
    {
        $a = $this->build($company, true);
        $now = now();
        if (!$a) { DB::table('company_credit_assessments')->where('company_id', $company->id)->delete(); return null; }
        $years = $this->statements($company)->where('is_consolidated', $a['basis'] === 'consolidated')->sortBy('financial_year')->values();
        $financial = !empty($this->m->sector($a['sector_key'])['financial_ratios']);
        $values = [];
        for ($i = 0; $i < $years->count(); $i++) {
            foreach ($this->ratios($years->slice(0, $i + 1)->values(), collect(), $financial) as $k => $v)
                $values[] = ['company_id' => $company->id, 'financial_year' => $years[$i]->financial_year, 'basis' => $a['basis'], 'ratio_key' => $k, 'value' => $v, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::transaction(function () use ($company, $a, $values, $now) {
            DB::table('company_ratio_values')->where('company_id', $company->id)->delete();
            foreach (array_chunk($values, 500) as $chunk) DB::table('company_ratio_values')->insert($chunk);
            $j = fn ($v) => $v === null ? null : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
            DB::table('company_credit_assessments')->updateOrInsert(['company_id' => $company->id], [
                'sector_key' => $a['sector_key'], 'basis' => $a['basis'], 'financial_year' => $a['year'], 'score' => $a['score'], 'standalone' => $a['standalone'],
                'indicative' => $a['indicative'], 'short_term' => $a['short_term'], 'liquidity' => $a['liquidity']['level'],
                'direction' => $a['outlook']['watch'] ? 'Rating watch – ' . $a['outlook']['watch']['type'] : $a['outlook']['direction'],
                'agency_rating' => $a['agency']['rating'] ?? null, 'agency_gap' => $a['agency']['gap'] ?? null, 'information_score' => $a['information']['score'],
                'pillars' => $j($a['pillars']), 'rows' => $j(array_values($a['rows'])), 'checks' => $j($a['checks']), 'adjustments' => $j($a['adjustments']),
                'policy' => $j($a['policy']), 'outlook' => $j($a['outlook']), 'information' => $j($a['information']), 'liquidity_detail' => $j($a['liquidity']), 'agency' => $j($a['agency']),
                'computed_at' => $now, 'updated_at' => $now, 'created_at' => $now,
            ]);
        });
        return $a;
    }

    /** Stored assessment shaped for the page; recomputed when missing or older than the stale_days setting. */
    public function stored(Company $company, bool $full): ?array
    {
        $row = DB::table('company_credit_assessments')->where('company_id', $company->id)->first();
        $stale = (int) ($this->m->setting('overlays', [])['stale_days'] ?? 7);
        if (!$row || !$row->computed_at || Carbon::parse($row->computed_at)->lt(now()->subDays($stale))) {
            if (!$this->persist($company)) return null;
            $row = DB::table('company_credit_assessments')->where('company_id', $company->id)->first();
        }
        $d = fn ($v) => json_decode((string) $v, true);
        // Dates come back from JSON as strings; restore them for the views.
        $dates = function (?array $a, array $paths) {
            foreach ($paths as $p) if (($v = data_get($a, $p)) && is_string($v)) data_set($a, $p, Carbon::parse($v));
            return $a;
        };
        $policy = $dates($d($row->policy), ['default.last_default', 'default.cured_on', 'inc.since', 'credit_enhanced.date', 'provisional.date', 'provisional.expires', 'withdrawal.date']);
        $agency = $dates($d($row->agency), ['date']);
        $outlook = $dates($d($row->outlook), ['watch.date']);
        $sector = $this->m->sector($row->sector_key);
        $peers = $full ? $this->peerMedians($row->sector_key) : [];
        $rows = collect($d($row->rows) ?? [])->map(fn ($r) => ['peer' => $peers[$r['key']] ?? null] + $r)->keyBy('key')->all();
        return [
            'sector_key' => $row->sector_key, 'sector' => $sector['name'] ?? $row->sector_key, 'criteria' => $sector['criteria'] ?? null, 'notes' => $sector['notes'] ?? [],
            'basis' => $row->basis, 'full' => $full, 'year' => $row->financial_year, 'computed_at' => Carbon::parse($row->computed_at),
            'rows' => $full ? $rows : [], 'pillars' => $full ? ($d($row->pillars) ?? []) : [], 'score' => $full && $row->score !== null ? (float) $row->score : null,
            'standalone' => $full ? $row->standalone : null, 'indicative' => $full ? $row->indicative : null, 'adjustments' => $full ? ($d($row->adjustments) ?? []) : [],
            'short_term' => $full ? $row->short_term : null, 'liquidity' => $d($row->liquidity_detail) ?? ['level' => $row->liquidity, 'agency' => null, 'why' => []],
            'checks' => $full ? ($d($row->checks) ?? []) : [], 'agency' => $agency, 'policy' => $policy ?? [],
            'outlook' => $outlook ?? ['direction' => $row->direction, 'agency' => null, 'probability' => null, 'watch' => null],
            'information' => $d($row->information) ?? ['score' => 0, 'missing' => []],
        ];
    }

    private function statements(Company $company): Collection
    {
        return $company->relationLoaded('financialStatements') ? $company->financialStatements : $company->financialStatements()->get();
    }

    private function ratings(Company $company): Collection
    {
        $key = Company::nameKey($company->name);
        return CreditRating::where(fn ($q) => $q->where('company_id', $company->id)->orWhere(fn ($q) => $q->where('name_key', $key)->where('name_key', '!=', '')))
            ->where('parse_status', 'parsed')->whereNotNull('published_on')->orderBy('published_on')->get();
    }
}
