<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CreditRating;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * A company's borrowing and funding lifecycle from three sources:
 *  - MCA charges (bank loans secured on assets: created, modified, satisfied, with the lender),
 *  - rating reports (rated limits by facility over time, and funding events read from the rationale:
 *    promoter/group infusions, CCD/NCD/bond raises, repayments, debt tie-ups, capex, utilisation),
 *  - audited financial statements (long- and short-term borrowings, EBITDA, interest, net worth).
 * Events are linked into stories (e.g. "₹320 cr CCDs from the parent → bank term loans repaid → charge satisfied")
 * and combined with the credit outlook model into a view of how much more the company could borrow.
 */
class FundingLifecycle
{
    public const INSTRUMENT_LABELS = ['ccd' => 'compulsorily convertible debentures', 'ocd' => 'optionally convertible debentures', 'ncd' => 'NCDs / bonds', 'preference' => 'preference shares',
        'equity' => 'equity', 'unsecured_loan' => 'unsecured loans', 'term_loan' => 'term loans', 'commercial_paper' => 'commercial paper'];

    public function __construct(private CreditOutlookModel $model) {}

    /** $full = false (non-premium viewers): rating-report data only; lenders, charges and audited figures are left out. */
    public function build(Company $company, bool $full = true): ?array
    {
        $ratings = $this->ratings($company);
        $charges = !$full ? collect() : ($company->relationLoaded('charges') ? $company->charges : $company->charges()->get());
        $statements = !$full ? collect() : ($company->relationLoaded('financialStatements') ? $company->financialStatements : $company->financialStatements()->get())
            ->sortBy('financial_year')->groupBy('financial_year')->map(fn ($g) => $g->firstWhere('is_consolidated', false) ?? $g->first())->values();
        if ($ratings->isEmpty() && $charges->isEmpty() && $statements->isEmpty()) return null;

        $events = collect();
        foreach ($charges as $c) {
            $lender = trim((string) $c->lender) ?: 'a lender';
            if ($c->created_on) $events->push(['date' => Carbon::parse($c->created_on), 'kind' => 'charge_created', 'title' => 'Loan secured: ' . $lender, 'amount' => $this->crore($c->amount), 'lender' => $lender, 'detail' => $c->assets_charged ? 'Charged on ' . mb_strimwidth((string) $c->assets_charged, 0, 140, '…') : null, 'source' => 'MCA charge ' . $c->charge_id]);
            if ($c->modified_on && $c->modified_on != $c->created_on) $events->push(['date' => Carbon::parse($c->modified_on), 'kind' => 'charge_modified', 'title' => 'Loan terms modified: ' . $lender, 'amount' => $this->crore($c->amount), 'lender' => $lender, 'source' => 'MCA charge ' . $c->charge_id]);
            if ($c->satisfied_on) $events->push(['date' => Carbon::parse($c->satisfied_on), 'kind' => 'charge_satisfied', 'title' => 'Loan closed (charge satisfied): ' . $lender, 'amount' => $this->crore($c->amount), 'lender' => $lender, 'source' => 'MCA charge ' . $c->charge_id]);
        }
        $facilities = [];
        $prev = null;
        foreach ($ratings as $r) {
            $by = $this->facilityMix($r);
            $facilities[] = ['date' => $r->published_on->toDateString(), 'agency' => $r->agency, 'total' => (float) ($r->total_rated_crore ?? array_sum($by)), 'by_type' => $by, 'rating' => $r->rating_long, 'action' => $r->action];
            $events->push(['date' => $r->published_on, 'kind' => 'rating', 'title' => $r->agency . ' ' . ($r->action ?: 'rating') . ': ' . ($r->rating_long ?: $r->rating_short ?: '—') . ($r->outlook ? ' (' . $r->outlook . ')' : ''),
                'amount' => $r->total_rated_crore, 'detail' => $r->total_rated_crore ? 'Rated limits ₹' . $this->fmt($r->total_rated_crore) . ' cr' : null, 'source' => $r->agency . ' rationale', 'url' => $r->source_url]);
            foreach ((array) $r->funding_events as $e) {
                $events->push(['date' => $r->published_on, 'kind' => 'funding_' . $e['type'], 'title' => $this->eventTitle($e), 'amount' => $e['amount_crore'] ?? null, 'detail' => null, 'evidence' => $e['evidence'] ?? null,
                    'from' => $e['from'] ?? null, 'instrument' => $e['instrument'] ?? null, 'source' => $r->agency . ' rationale', 'url' => $r->source_url, 'raw' => $e]);
            }
            // Facility shifts between two reports of the same agency: term loans gone, unallocated limits appearing.
            if ($prev && $prev->agency === $r->agency) {
                $was = $this->facilityMix($prev);
                foreach (['Term loans' => 'repaid or reclassified', 'Unallocated / proposed' => 'added'] as $type => $_) {
                    $a = $was[$type] ?? 0; $b = $by[$type] ?? 0;
                    if ($type === 'Term loans' && $a > 0 && $b < $a * 0.5) $events->push(['date' => $r->published_on, 'kind' => 'facility_drop', 'title' => 'Rated term loans fell from ₹' . $this->fmt($a) . ' cr to ₹' . $this->fmt($b) . ' cr', 'amount' => $a - $b, 'source' => $r->agency . ' rationale', 'url' => $r->source_url]);
                    if ($type === 'Unallocated / proposed' && $b > $a) $events->push(['date' => $r->published_on, 'kind' => 'facility_headroom', 'title' => '₹' . $this->fmt($b - $a) . ' cr of limits rated but not yet allocated to a lender', 'amount' => $b - $a, 'source' => $r->agency . ' rationale', 'url' => $r->source_url]);
                }
            }
            $prev = $r;
        }
        $debt = $statements->map(fn ($s) => ['year' => $s->financial_year, 'long_term' => $this->crore($s->long_term_borrowings), 'short_term' => $this->crore($s->short_term_borrowings),
            'total' => $this->crore($s->total_debt ?: ((float) $s->long_term_borrowings + (float) $s->short_term_borrowings)), 'net_worth' => $this->crore($s->net_worth),
            'ebitda' => $this->crore($s->ebitda), 'finance_cost' => $this->crore($s->finance_cost), 'revenue' => $this->crore($s->revenue)])->values();
        foreach ($debt as $i => $d) {
            if ($i === 0 || !$debt[$i - 1]['total'] || $d['total'] === null) continue;
            $chg = $d['total'] - $debt[$i - 1]['total'];
            if (abs($chg) >= max(5, $debt[$i - 1]['total'] * 0.25)) $events->push(['date' => $this->fyEnd($d['year']), 'kind' => $chg > 0 ? 'debt_up' : 'debt_down',
                'title' => 'Borrowings ' . ($chg > 0 ? 'rose' : 'fell') . ' by ₹' . $this->fmt(abs($chg)) . ' cr in ' . $d['year'] . ' (to ₹' . $this->fmt($d['total']) . ' cr)', 'amount' => abs($chg), 'source' => 'Audited balance sheet']);
        }
        $events = $events->filter(fn ($e) => $e['date'])->sortByDesc(fn ($e) => $e['date']->timestamp)->values();
        $openCharges = $charges->filter(fn ($c) => !$c->satisfied_on && strtolower((string) $c->status) !== 'closed');
        return [
            'full' => $full,
            'timeline' => $events,
            'stories' => $this->stories($events, $charges),
            'facilities' => $facilities,
            'debt' => $debt,
            'lenders' => $charges->groupBy(fn ($c) => trim((string) $c->lender) ?: 'Unnamed')->map(fn ($g, $name) => ['lender' => $name, 'charges' => $g->count(), 'open' => $g->whereNull('satisfied_on')->count(),
                'amount_open' => round($g->whereNull('satisfied_on')->sum(fn ($c) => $this->crore($c->amount) ?? 0), 2), 'first' => $g->min('created_on'), 'last_closed' => $g->max('satisfied_on')])->sortByDesc('amount_open')->values(),
            'open_charges' => ['count' => $openCharges->count(), 'amount' => round($openCharges->sum(fn ($c) => $this->crore($c->amount) ?? 0), 2)],
            'capacity' => $this->capacity($ratings, $debt, $openCharges),
        ];
    }

    /** Linked sequences: funding in → debt repaid → bank charge closed; capex → new loan; limits enhanced. */
    private function stories(Collection $events, Collection $charges): array
    {
        $out = [];
        foreach ($events->where('kind', 'funding_inflow') as $in) {
            $repaid = $events->first(fn ($e) => $e['kind'] === 'funding_repayment' && abs($e['date']->diffInDays($in['date'])) <= 400);
            $closed = $events->filter(fn ($e) => $e['kind'] === 'charge_satisfied' && $e['date']->between($in['date']->copy()->subMonths(9), $in['date']->copy()->addMonths(15)));
            $drop = $events->first(fn ($e) => $e['kind'] === 'facility_drop' && abs($e['date']->diffInDays($in['date'])) <= 400);
            if (!$repaid && $closed->isEmpty() && !$drop) continue;
            $steps = [['date' => $in['date'], 'text' => '₹' . $this->fmt($in['amount']) . ' cr raised' . ($in['instrument'] ? ' through ' . (self::INSTRUMENT_LABELS[$in['instrument']] ?? $in['instrument']) : '') . ($in['from'] ? ' from ' . $in['from'] : '')]];
            if ($repaid) $steps[] = ['date' => $repaid['date'], 'text' => ucfirst($repaid['raw']['scope'] ?? 'full') . ' repayment of ' . ($repaid['raw']['debt'] ?? 'debt')];
            if ($drop) $steps[] = ['date' => $drop['date'], 'text' => $drop['title']];
            foreach ($closed->take(4) as $c) $steps[] = ['date' => $c['date'], 'text' => 'Charge with ' . $c['lender'] . ($c['amount'] ? ' (₹' . $this->fmt($c['amount']) . ' cr)' : '') . ' satisfied'];
            $out[] = ['title' => 'Refinanced bank debt with ' . ($in['instrument'] ? (self::INSTRUMENT_LABELS[$in['instrument']] ?? 'new funding') : 'new funding') . ($in['from'] ? ' from ' . $in['from'] : ''),
                'steps' => collect($steps)->sortBy(fn ($s) => $s['date']->timestamp)->values()->all(), 'evidence' => $in['evidence'] ?? null];
        }
        foreach ($events->where('kind', 'funding_capex') as $cx) {
            $loans = $events->filter(fn ($e) => $e['kind'] === 'charge_created' && $e['date']->between($cx['date']->copy()->subMonths(24), $cx['date']->copy()->addMonths(12)));
            $tie = $events->first(fn ($e) => $e['kind'] === 'funding_borrowing' && abs($e['date']->diffInDays($cx['date'])) <= 400);
            if ($loans->isEmpty() && !$tie) continue;
            $steps = [['date' => $cx['date'], 'text' => 'Project / plant of ₹' . $this->fmt($cx['amount']) . ' cr' . (!empty($cx['raw']['funding_mix']) ? ', debt-to-equity ' . $cx['raw']['funding_mix'] : '')]];
            if ($tie) $steps[] = ['date' => $tie['date'], 'text' => 'Term debt tied up' . ($tie['amount'] ? ' (₹' . $this->fmt($tie['amount']) . ' cr)' : '')];
            foreach ($loans->take(4) as $l) $steps[] = ['date' => $l['date'], 'text' => 'Loan secured with ' . $l['lender'] . ($l['amount'] ? ' (₹' . $this->fmt($l['amount']) . ' cr)' : '')];
            $out[] = ['title' => 'Capex funded with bank debt', 'steps' => collect($steps)->sortBy(fn ($s) => $s['date']->timestamp)->values()->all(), 'evidence' => $cx['evidence'] ?? null];
        }
        // Lender switch: a charge with one lender closed and another created within 6 months.
        foreach ($events->where('kind', 'charge_satisfied') as $c) {
            $new = $events->first(fn ($e) => $e['kind'] === 'charge_created' && ($e['lender'] ?? '') !== ($c['lender'] ?? '') && abs($e['date']->diffInDays($c['date'])) <= 183);
            if ($new) $out[] = ['title' => 'Moved borrowing from ' . $c['lender'] . ' to ' . $new['lender'], 'steps' => collect([['date' => $c['date'], 'text' => 'Charge with ' . $c['lender'] . ' satisfied'], ['date' => $new['date'], 'text' => 'New charge with ' . $new['lender'] . ($new['amount'] ? ' (₹' . $this->fmt($new['amount']) . ' cr)' : '')]])->sortBy(fn ($s) => $s['date']->timestamp)->values()->all(), 'evidence' => null];
        }
        return collect($out)->unique('title')->take(8)->values()->all();
    }

    /** How much more the company could borrow, and how likely lenders are to agree. */
    private function capacity(Collection $ratings, Collection $debt, Collection $openCharges): array
    {
        $latest = $ratings->where('withdrawn', false)->last();
        $previous = $latest ? $ratings->filter(fn ($r) => $r->agency === $latest->agency && $r->published_on->lt($latest->published_on))->last() : null;
        $outlook = $latest ? $this->model->predict($latest, $previous) : null;
        $mix = $latest ? $this->facilityMix($latest) : [];
        $metrics = $latest ? $this->model->latestMetrics($latest->financials) : [];
        $last = $debt->filter(fn ($d) => $d['total'] !== null)->last();
        $ebitda = $last['ebitda'] ?? ($metrics['ebitda'] ?? null);
        $totalDebt = $last['total'] ?? null;
        // Lenders commonly cap total debt near 3x EBITDA for mid-rated borrowers (stricter below investment grade).
        $cap = $latest && $latest->score !== null && $latest->score >= 11 ? 3.5 : 2.5;
        $room = $ebitda !== null && $ebitda > 0 && $totalDebt !== null ? round(max(0, $cap * $ebitda - $totalDebt), 1) : null;
        $icr = $metrics['interest_coverage'] ?? ($last && $last['finance_cost'] ? round(($last['ebitda'] ?? 0) / max(0.01, $last['finance_cost']), 2) : null);
        $util = collect($ratings->last()?->funding_events ?? [])->firstWhere('type', 'utilisation')['percent'] ?? null;
        $unallocated = $mix['Unallocated / proposed'] ?? 0;
        $p = $outlook['probability'] ?? null;
        $score = 0; $why = [];
        if ($p !== null) { $score += $p >= 0.8 ? 2 : ($p >= 0.6 ? 1 : -1); $why[] = 'Credit outlook model: ' . round($p * 100) . '% chance the next rating action is stable or better'; }
        if ($latest?->score !== null) { $score += $latest->score >= 11 ? 1 : ($latest->score <= 7 ? -1 : 0); $why[] = 'Current rating ' . $latest->rating_long . ($latest->score >= 11 ? ' (investment grade)' : ' (below investment grade)'); }
        if ($room !== null) { $score += $room > 0 ? 1 : -1; $why[] = $room > 0 ? 'About ₹' . $this->fmt($room) . ' cr more debt fits within ' . $cap . '× EBITDA' : 'Debt already above ' . $cap . '× EBITDA'; }
        if ($icr !== null) { $score += $icr >= 2.5 ? 1 : ($icr < 1.5 ? -1 : 0); $why[] = 'Interest coverage ' . $this->fmt($icr) . '×'; }
        if ($util !== null) { $score += $util <= 75 ? 1 : -1; $why[] = 'Working-capital limits ' . $util . '% utilised on average'; }
        if ($unallocated > 0) { $score += 1; $why[] = '₹' . $this->fmt($unallocated) . ' cr of rated limits not yet tied to a lender'; }
        if ($latest?->non_cooperating) { $score -= 2; $why[] = 'Issuer not cooperating with the rating agency'; }
        $newest = $ratings->last();
        if ($newest && $newest->withdrawn) $why[] = $newest->agency . ' withdrew its ratings on ' . $newest->published_on->format('d M Y') . ' — usually after the rated bank loans were repaid or moved to another lender';
        $verdict = $score >= 4 ? 'Further bank funding likely' : ($score >= 1 ? 'Further funding possible with conditions' : 'Further bank funding difficult');
        return ['verdict' => $verdict, 'score' => $score, 'why' => $why, 'outlook' => $outlook, 'room_crore' => $room, 'debt_cap_multiple' => $cap, 'interest_coverage' => $icr,
            'utilisation' => $util, 'unallocated' => $unallocated, 'rated_total' => $latest?->total_rated_crore, 'mix' => $mix, 'open_charges' => round($openCharges->sum(fn ($c) => $this->crore($c->amount) ?? 0), 2),
            'model' => $this->model->model() ? array_intersect_key($this->model->model(), array_flip(['pairs', 'test_auc', 'test_accuracy', 'trained_at'])) : null];
    }

    /** Rated limits by facility group for one rating report (₹ crore). */
    public function facilityMix(CreditRating $r): array
    {
        $mix = [];
        foreach ((array) $r->instruments as $i) {
            if (!empty($i['withdrawn'])) continue;
            $amt = (float) ($i['current'] ?? 0);
            if ($amt <= 0) continue;
            $name = strtolower(($i['instrument'] ?? '') . ' ' . ($i['type'] ?? ''));
            $group = match (true) {
                (bool) preg_match('/unallocated|proposed/', $name) => 'Unallocated / proposed',
                (bool) preg_match('/term loan|term debt/', $name) => 'Term loans',
                (bool) preg_match('/ncd|debenture|bond/', $name) => 'NCDs / bonds',
                (bool) preg_match('/commercial paper/', $name) => 'Commercial paper',
                (bool) preg_match('/non[- ]?fund|letter of credit|bank guarantee|\blc\b|\bbg\b/', $name) => 'Non-fund based (LC/BG)',
                (bool) preg_match('/cash credit|working capital|overdraft|packing|fund[- ]based|wcdl/', $name) => 'Working capital',
                (bool) preg_match('/fixed deposit/', $name) => 'Fixed deposits',
                default => 'Other',
            };
            $mix[$group] = round(($mix[$group] ?? 0) + $amt, 2);
        }
        arsort($mix);
        return $mix;
    }

    private function ratings(Company $company): Collection
    {
        $key = Company::nameKey($company->name);
        return CreditRating::where(fn ($q) => $q->where('company_id', $company->id)->orWhere(fn ($q) => $q->where('name_key', $key)->where('name_key', '!=', '')))
            ->where('parse_status', 'parsed')->whereNotNull('published_on')->orderBy('published_on')->get();
    }

    private function eventTitle(array $e): string
    {
        $amt = isset($e['amount_crore']) ? '₹' . $this->fmt($e['amount_crore']) . ' cr ' : '';
        $inst = isset($e['instrument']) ? (self::INSTRUMENT_LABELS[$e['instrument']] ?? $e['instrument']) : null;
        return match ($e['type']) {
            'inflow' => $amt . 'raised' . ($inst ? ' through ' . $inst : '') . (!empty($e['from']) ? ' from ' . $e['from'] : ''),
            'repayment' => ucfirst($e['scope'] ?? 'full') . ' repayment of ' . ($e['debt'] ?? 'debt') . ($amt ? ' (' . trim($amt) . ')' : ''),
            'borrowing' => 'New borrowing arranged' . ($amt ? ': ' . trim($amt) : '') . ($inst ? ' (' . $inst . ')' : ''),
            'capex' => 'Capex / project: ' . trim($amt) . (!empty($e['funding_mix']) ? ', debt-to-equity ' . $e['funding_mix'] : ''),
            'utilisation' => ucfirst($e['kind'] ?? 'average') . ' working-capital utilisation ' . ($e['percent'] ?? '?') . '%',
            'liquidity' => 'Cash & liquid funds ' . trim($amt) . (!empty($e['undrawn_lines']) ? ', undrawn lines ₹' . $this->fmt($e['undrawn_lines']) . ' cr' : ''),
            'obligation' => 'Debt repayments due' . (!empty($e['year']) ? ' in ' . $e['year'] : '') . ': ' . trim($amt),
            default => ucfirst($e['type']),
        };
    }

    // MCA charges and audited statements are stored in rupees; everything on this page is in ₹ crore.
    private function crore($v): ?float
    {
        return $v === null || $v === '' ? null : round((float) $v / 1e7, 2);
    }

    private function fyEnd(?string $fy): ?Carbon
    {
        return $fy && preg_match('/(20\d\d)\D*(\d{2,4})?$/', $fy, $m) ? Carbon::create(isset($m[2]) ? (strlen($m[2]) === 2 ? (int) ('20' . $m[2]) : (int) $m[2]) : (int) $m[1], 3, 31) : null;
    }

    private function fmt($v): string
    {
        return $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    }
}
