<?php
namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CreditRating;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Credit ratings and rated debt for a company page, computed from the stored rating actions:
 * current rating per agency, rating score history and notch changes, rated debt over time and by facility type,
 * debt instruments with a maturity ladder, weighted average coupon and remaining tenor, and agency-reported
 * financials with derived growth, EBITDA, debt and interest figures.
 */
class CreditProfile
{
    public function build(Company $company): ?array
    {
        // Ratings filed under the same normalised name count too (a company can exist twice, e.g. "X" and "X Limited").
        $key = Company::nameKey($company->name);
        $ratings = CreditRating::where(fn ($q) => $q->where('company_id', $company->id)->orWhere(fn ($q) => $q->where('name_key', $key)->where('name_key', '!=', '')))->get();
        $ratings = $ratings->where('parse_status', 'parsed')->filter(fn ($r) => $r->published_on)->sortBy('published_on')->values();
        $sources = $company->relationLoaded('creditRatingSources') ? $company->creditRatingSources : $company->creditRatingSources()->get();
        if ($ratings->isEmpty() && $sources->every(fn ($s) => empty($s->current))) return null;

        $latest = $ratings->last();
        $current = $ratings->groupBy('agency')->map(fn ($list) => $list->last())->sortByDesc(fn ($r) => $r->published_on)->values();
        $rated = $ratings->filter(fn ($r) => $r->score !== null);
        $scored = $rated->filter(fn ($r) => !$r->withdrawn);
        $yearAgo = $scored->filter(fn ($r) => $r->published_on->lte(now()->subYear()))->last();
        $now = $scored->last();
        $first = $scored->first();
        $actions = $ratings->countBy('action');

        return [
            'latest' => $latest,
            'current' => $current,
            'care' => $sources->firstWhere('agency', 'CARE'),
            'history' => $ratings->sortByDesc('published_on')->values(),
            'counts' => [
                'actions' => $ratings->count(),
                'upgrades' => $actions['upgraded'] ?? 0,
                'downgrades' => $actions['downgraded'] ?? 0,
                'agencies' => $ratings->pluck('agency')->unique()->count(),
                'since' => $ratings->first()?->published_on,
            ],
            // Positive = better rating than a year ago / than the first rating on record (notches on the AAA…D scale).
            'notches_1y' => $now && $yearAgo ? $now->score - $yearAgo->score : null,
            'notches_total' => $now && $first && $now !== $first ? $now->score - $first->score : null,
            'chart' => $this->scoreChart($rated),
            'debt' => $this->debt($ratings),
            'financials' => $this->financials($ratings),
            'flags' => $this->flags($ratings, $latest),
        ];
    }

    /** Step chart of the long-term rating score per agency (20 = AAA … 1 = D). */
    private function scoreChart(Collection $rated): ?array
    {
        if ($rated->count() < 1) return null;
        $colors = ['ICRA' => '#1d4ed8', 'CARE' => '#0f766e'];
        $datasets = $rated->groupBy('agency')->map(fn ($list, $agency) => [
            'label' => $agency, 'stepped' => true, 'borderColor' => $colors[$agency] ?? '#7c3aed', 'backgroundColor' => $colors[$agency] ?? '#7c3aed', 'pointRadius' => 4,
            'data' => $list->map(fn ($r) => ['x' => $r->published_on->toDateString(), 'y' => $r->score, 'rating' => $r->rating_long])->values()->all(),
        ])->values()->all();
        $scale = array_reverse(RatingRationaleParser::LONG_SCALE);
        return ['datasets' => $datasets, 'labels' => array_combine(range(1, count($scale)), $scale)];
    }

    /** Rated debt: totals over time, facility mix in the latest release per agency, instruments and maturities. */
    private function debt(Collection $ratings): array
    {
        $trend = $ratings->filter(fn ($r) => $r->total_rated_crore !== null && !$r->withdrawn)
            ->map(fn ($r) => ['date' => $r->published_on->toDateString(), 'agency' => $r->agency, 'total' => round($r->total_rated_crore, 2)])->values();
        // Agencies rate the same borrowings, so the larger latest programme (one agency) is used, never the sum of both.
        $latestPerAgency = $ratings->filter(fn ($r) => $r->instruments && !$r->withdrawn)->groupBy('agency')->map(fn ($l) => $l->last())
            ->sortByDesc(fn ($r) => $r->total_rated_crore ?? collect($r->instruments)->sum('current'))->take(1);
        $facilities = $latestPerAgency->flatMap(fn ($r) => collect($r->instruments)->map(fn ($i) => $i + ['agency' => $r->agency, 'as_of' => $r->published_on]))
            ->filter(fn ($i) => ($i['current'] ?? null) !== null && !($i['withdrawn'] ?? false))->values();
        $mix = $facilities->groupBy('type')->map(fn ($l) => round($l->sum('current'), 2))->filter()->sortDesc();
        $outstanding = $mix->sum();

        // Instruments: the newest record of each ISIN (or instrument + maturity) across all releases.
        $instruments = $ratings->sortByDesc('published_on')->flatMap(fn ($r) => collect($r->debt_instruments ?? [])->map(fn ($i) => $this->classify($i) + ['agency' => $r->agency, 'as_of' => $r->published_on->toDateString()]))
            ->unique(fn ($i) => $i['isin'] ?: (($i['instrument'] ?? '') . '|' . ($i['maturity_on'] ?? '') . '|' . ($i['amount_crore'] ?? '')))
            // Individual issues only (an ISIN or a maturity date); programme-level limits are listed under rated facilities.
            ->filter(fn ($i) => $i['isin'] || ($i['maturity_on'] ?? null))
            ->map(function ($i) { if (preg_match('/CARE Ratings|ISIN|earlier|Simple|Proposed|^days|^\W*$/i', (string) ($i['instrument'] ?? ''))) $i['instrument'] = null; return $i; })->values();
        $today = now()->startOfDay();
        $live = $instruments->filter(fn ($i) => !($i['withdrawn'] ?? false) && ($i['amount_crore'] ?? 0) > 0 && (!($i['maturity_on'] ?? null) || Carbon::parse($i['maturity_on'])->gte($today)));
        $dated = $live->filter(fn ($i) => $i['maturity_on'] ?? null);
        $ladder = $dated->groupBy(fn ($i) => substr($i['maturity_on'], 0, 4))->map(fn ($l) => round($l->sum('amount_crore'), 2))->sortKeys();
        // Fixed coupons only: "Repo Rate + 2.80%" style spreads are not the cost of the debt.
        $couponed = $live->filter(fn ($i) => ($i['coupon'] ?? null) !== null && $i['coupon'] >= 3 && !str_contains((string) ($i['instrument'] ?? ''), '+'));
        $weight = $couponed->sum('amount_crore');
        $tenorWeight = $dated->sum('amount_crore');
        $due12 = $dated->filter(fn ($i) => Carbon::parse($i['maturity_on'])->lte($today->copy()->addYear()))->sum('amount_crore');

        // How often rated borrowing limits were raised (current > previous) across releases.
        $enhancements = $ratings->sum(fn ($r) => collect($r->instruments ?? [])->filter(fn ($i) => ($i['previous'] ?? null) !== null && ($i['current'] ?? null) !== null && $i['current'] > $i['previous'])->count());
        $newFacilities = $ratings->sum(fn ($r) => collect($r->instruments ?? [])->where('action', 'assigned')->count());

        return [
            'trend' => $trend,
            'mix' => $mix,
            'outstanding' => $outstanding ?: null,
            'facilities' => $facilities,
            // Outstanding first (by maturity), then matured / withdrawn, newest first.
            'instruments' => $instruments->sortBy(function ($i) use ($today) {
                $done = ($i['withdrawn'] ?? false) || (($i['maturity_on'] ?? null) && Carbon::parse($i['maturity_on'])->lt($today));
                return $done ? '1' . (99999999 - (int) str_replace('-', '', (string) ($i['maturity_on'] ?? '0'))) : '0' . ($i['maturity_on'] ?? '9999');
            })->values(),
            'ladder' => $ladder,
            'weighted_coupon' => $weight > 0 ? round($couponed->sum(fn ($i) => $i['coupon'] * $i['amount_crore']) / $weight, 2) : null,
            'avg_tenor_years' => $tenorWeight > 0 ? round($dated->sum(fn ($i) => $today->diffInDays(Carbon::parse($i['maturity_on'])) / 365.25 * $i['amount_crore']) / $tenorWeight, 1) : null,
            'next_maturity' => $dated->sortBy('maturity_on')->first(),
            'due_12m' => $due12 ?: null,
            'enhancements' => $enhancements,
            'new_facilities' => $newFacilities,
            'peak' => $trend->sortByDesc('total')->first(),
        ];
    }

    /**
     * The latest agency financial table, with derived rows: revenue growth, EBITDA (margin × income when only the margin
     * is given), implied debt (debt/EBITDA × EBITDA) and implied interest (EBITDA ÷ interest coverage).
     */
    /** Instrument type from the ISIN security code when the text gave none (INE…07/08… debentures, INE…14… commercial paper). */
    private function classify(array $i): array
    {
        if (($i['type'] ?? 'Other') === 'Other' && preg_match('/^IN[EF][A-Z0-9]{4}(\d{2})/', (string) ($i['isin'] ?? ''), $m)) {
            $i['type'] = match ($m[1]) { '07', '08' => 'Debentures & bonds', '14' => 'Commercial paper', '15', '16' => 'Securitisation (PTCs)', default => 'Other' };
        }
        return $i;
    }

    private function financials(Collection $ratings): ?array
    {
        $source = $ratings->filter(fn ($r) => !empty($r->financials['rows']))->last();
        if (!$source) return null;
        $fin = $source->financials;
        $periods = $fin['periods'];
        $rows = collect($fin['rows']);
        $get = fn ($metric) => $rows->firstWhere('metric', $metric)['values'] ?? null;
        $revenue = $get('revenue') ?? $get('total_income');
        $derived = [];
        if ($revenue) {
            $growth = [];
            foreach ($revenue as $i => $v) {
                $prev = $revenue[$i - 1] ?? null;
                $growth[] = $i > 0 && $prev && $v !== null && !($periods[$i]['partial'] ?? false) && !($periods[$i - 1]['partial'] ?? false) ? round(($v - $prev) / abs($prev) * 100, 1) : null;
            }
            if (array_filter($growth, fn ($g) => $g !== null)) $derived[] = ['label' => 'Revenue growth (YoY)', 'unit' => '%', 'values' => $growth];
        }
        $ebitda = $get('ebitda');
        if (!$ebitda && ($margin = $get('ebitda_margin')) && $revenue) {
            $ebitda = array_map(fn ($m, $r) => $m !== null && $r !== null ? round($m * $r / 100, 2) : null, $margin, $revenue);
            $derived[] = ['label' => 'EBITDA (margin × income)', 'unit' => 'crore', 'values' => $ebitda];
        } elseif ($ebitda && $revenue) {
            $derived[] = ['label' => 'EBITDA margin', 'unit' => '%', 'values' => array_map(fn ($e, $r) => $e !== null && $r ? round($e / $r * 100, 1) : null, $ebitda, $revenue)];
        }
        if ($ebitda && ($lev = $get('debt_to_ebitda'))) {
            $derived[] = ['label' => 'Implied total debt (debt/EBITDA × EBITDA)', 'unit' => 'crore', 'values' => array_map(fn ($x, $e) => $x !== null && $e !== null && $e > 0 ? round($x * $e, 2) : null, $lev, $ebitda)];
        }
        if ($ebitda && ($cov = $get('interest_coverage'))) {
            $derived[] = ['label' => 'Implied interest cost (EBITDA ÷ coverage)', 'unit' => 'crore', 'values' => array_map(fn ($c, $e) => $c !== null && $c > 0 && $e !== null && $e > 0 ? round($e / $c, 2) : null, $cov, $ebitda)];
        }
        if (($pat = $get('pat')) && $revenue && !$get('pat_margin')) {
            $derived[] = ['label' => 'PAT margin', 'unit' => '%', 'values' => array_map(fn ($p, $r) => $p !== null && $r ? round($p / $r * 100, 1) : null, $pat, $revenue)];
        }
        return ['periods' => $periods, 'rows' => $rows->all(), 'derived' => $derived, 'agency' => $source->agency, 'as_of' => $source->published_on, 'url' => $source->source_url];
    }

    private function flags(Collection $ratings, ?CreditRating $latest): array
    {
        $flags = [];
        $recent = $ratings->filter(fn ($r) => $r->published_on->gte(now()->subYear()));
        if ($d = $recent->where('action', 'downgraded')->last()) $flags[] = ['bad', 'Downgraded by ' . $d->agency . ' on ' . $d->published_on->format('d M Y') . ($d->previous_rating_long ? ' (from ' . $d->previous_rating_long . ')' : '')];
        if ($u = $recent->where('action', 'upgraded')->last()) $flags[] = ['good', 'Upgraded by ' . $u->agency . ' on ' . $u->published_on->format('d M Y') . ($u->previous_rating_long ? ' (from ' . $u->previous_rating_long . ')' : '')];
        if ($latest?->non_cooperating) $flags[] = ['bad', 'Issuer not cooperating with ' . $latest->agency . ' (rating based on limited information)'];
        if ($latest && $latest->outlook === 'Negative') $flags[] = ['bad', 'Negative outlook (' . $latest->agency . ')'];
        if ($latest && $latest->action === 'watch') $flags[] = ['warn', 'Rating watch (' . $latest->agency . ', ' . $latest->published_on->format('d M Y') . ')'];
        if ($latest && in_array($latest->liquidity, ['Stretched', 'Poor'], true)) $flags[] = ['bad', 'Liquidity assessed as ' . $latest->liquidity];
        if ($latest?->withdrawn) $flags[] = ['warn', 'Latest action: rating withdrawn (' . $latest->agency . ', ' . $latest->published_on->format('d M Y') . ')'];
        return $flags;
    }
}
