<?php

namespace App\Services\Funds;

use App\Services\Insights\Methodology;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fund credit quality per ICRA's Rating Methodology – Mutual Funds (June 2026), from the stored month-end portfolios:
 *  - security score from its rating and residual maturity (the default-rate table shared with the base method); a
 *    short-term-only rating takes the lowest long-term rating it maps to; unrated paper a shadow rating; InvIT units
 *    the InvIT rating notched down; CDMDF units notional AAA (A+ / one notch lower for A3 units in a dislocation);
 *  - fund score = market-value-weighted average; maturity considered = higher of the fund's WAM and its category's
 *    benchmark maturity (typical residual maturity of the category); rating = highest level whose benchmark score at that
 *    maturity the fund score does not exceed;
 *  - first rating needs all of the last three month-ends within the band (provisional until then, except gilt-only
 *    schemes); a breach gets one curing month unless it is sharp; an upgrade needs three consecutive supporting months;
 *  - an AMC's overnight / intraday bank lines anchor on its weakest short-duration debt scheme.
 */
class IcraFundQuality
{
    private const LONG = ['AAA', 'AA+', 'AA', 'AA-', 'A+', 'A', 'A-', 'BBB+', 'BBB', 'BBB-', 'BB+', 'BB', 'BB-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D'];
    public const LINE_CATEGORIES = ['Overnight Fund', 'Liquid Fund', 'Money Market Fund', 'Ultra Short Duration Fund', 'Low Duration Fund'];

    public function __construct(private Methodology $m) {}

    private function cfg(): array
    {
        return ($this->m->setting('icra_fund', []) ?: []) + config('fund_portfolios.icra');
    }

    private function pd(): array
    {
        return ($this->m->setting('fund_pd_annual', []) ?: []) + config('fund_portfolios.fund_pd_annual');
    }

    /** Rates every scheme with a portfolio at this month-end. Returns schemes rated. */
    public function scoreMonth(string $asOf): int
    {
        $holdings = DB::table('mf_portfolio_holdings')->where('as_of', $asOf)->get()->groupBy('scheme_key');
        $categories = $this->categories($holdings->map(fn ($r) => $r->first()->scheme_name)->all());
        [$byIssuer, $byName] = $this->longRatings($asOf);
        // Internal view for short-term-only paper of issuers with no long-term rating on file: the median long-term rating
        // of this month's issuers of the same short-term grade whose long-term rating is known.
        $idx = fn ($r) => array_search($r, self::LONG, true);
        $view = $holdings->flatten(1)->where('asset_type', 'short')->groupBy('rating')->map(function ($g) use ($byIssuer, $byName, $idx) {
            $known = $g->unique('issuer_key')->map(fn ($h) => ($h->issuer_key ? ($byIssuer[$h->issuer_key] ?? null) : null) ?? ($byName[\App\Models\Company::nameKey($h->instrument)] ?? null))
                ->filter()->map($idx)->sort()->values();
            return $known->count() >= 5 ? self::LONG[$known[intdiv($known->count() - 1, 2)]] : null;
        })->filter()->all();
        $scored = [];
        foreach ($holdings as $key => $rows) {
            if ($s = $this->score($rows, $byIssuer, $byName, $view)) $scored[$key] = $s + ['category' => $categories[$key] ?? null];
        }
        // Benchmark maturity per category: the owner's value, else the median WAM of the category's schemes this month.
        $cfg = $this->cfg();
        $median = collect($scored)->whereNotNull('category')->groupBy('category')->map(function ($g) {
            $w = $g->pluck('wam')->sort()->values();
            return $w->count() ? ($w->count() % 2 ? $w[intdiv($w->count(), 2)] : ($w[$w->count() / 2 - 1] + $w[$w->count() / 2]) / 2) : null;
        });
        $n = 0;
        foreach ($scored as $key => $s) {
            $catMat = $s['category'] !== null ? (float) ($cfg['category_maturity'][$s['category']] ?? $median[$s['category']] ?? 0) : 0.0;
            $t = max($s['wam'], $catMat, 1 / 12);
            [$implied, $benchmark] = $this->implied($s['score'], $t);
            $first = $holdings[$key]->first();
            [$rating, $status] = $this->publish($key, $asOf, $implied, $s['gilt_only']);
            DB::table('mf_icra_credit_quality')->updateOrInsert(['scheme_key' => $key, 'as_of' => $asOf], [
                'amc' => $first->amc, 'scheme_name' => $first->scheme_name, 'category' => $s['category'],
                'credit_score' => $s['score'], 'wam_years' => $s['wam'], 'category_maturity' => $catMat ?: null, 'maturity_considered' => round($t, 3),
                'benchmark_score' => $benchmark, 'implied' => $implied, 'rating' => $rating,
                'rating_short' => $s['short_eligible'] ? ($cfg['long_to_short'][$rating] ?? null) : null, 'status' => $status,
                'flags' => json_encode($s['flags'], JSON_UNESCAPED_UNICODE), 'updated_at' => now(), 'created_at' => now(),
            ]);
            $n++;
        }
        return $n;
    }

    /**
     * Long-term ratings for issuers of short-term-only paper: the lowest long-term rating of the same issuer's paper in any
     * portfolio over the last six months (ISIN issuer code), and the lowest current long-term agency rating on file by name.
     */
    private function longRatings(string $asOf): array
    {
        $idx = fn ($r) => array_search($r, self::LONG, true);
        $byIssuer = DB::table('mf_portfolio_holdings')->where('asset_type', 'long')->whereNotNull('rating')->whereNotNull('issuer_key')
            ->whereBetween('as_of', [Carbon::parse($asOf)->subMonths(6)->toDateString(), $asOf])->get(['issuer_key', 'rating'])
            ->groupBy('issuer_key')->map(fn ($g) => $g->pluck('rating')->filter(fn ($r) => $idx($r) !== false)->sortByDesc($idx)->first())->filter()->all();
        $byName = rescue(fn () => DB::table('credit_ratings')->where('withdrawn', false)->whereNotNull('rating_long')->whereNotNull('name_key')->orderByDesc('published_on')->get(['name_key', 'agency', 'rating_long'])
            ->groupBy('name_key')->map(fn ($g) => $g->unique('agency')->pluck('rating_long')->map(fn ($r) => preg_replace('/^(?:CARE|ICRA|CRISIL|IND|BWR|ACUITE|INFOMERICS)\s*|\s*\(.*$|;.*$/i', '', (string) $r))
                ->filter(fn ($r) => $idx($r) !== false)->sortByDesc($idx)->first())->filter()->all(), [], false);
        return [$byIssuer, $byName];
    }

    public function score(Collection $rows, array $byIssuer = [], array $byName = [], array $view = []): ?array
    {
        $cfg = $this->cfg();
        $pd = $this->pd();
        $asOf = Carbon::parse($rows->first()->as_of);
        $total = $rows->sum('pct_nav');
        $debtLike = $rows->whereIn('asset_type', ['long', 'short', 'unrated', 'sovereign', 'cash'])->sum('pct_nav');
        $minDebt = (float) (config('fund_portfolios.fund_rules')['min_debt_share'] ?? 65);
        if ($total < 80 || $debtLike < $minDebt || preg_match('/\b(gold|silver|commodit\w*)\b/i', (string) $rows->first()->scheme_name)) return null;

        $known = DB::table('mf_portfolio_holdings')->whereIn('isin', $rows->whereNull('maturity_on')->pluck('isin')->filter()->unique())->whereNotNull('maturity_on')
            ->orderByDesc('as_of')->pluck('maturity_on', 'isin');
        $flags = []; $score = 0.0; $wSum = 0.0; $wYears = 0.0; $maxYears = 0.0; $assumed = 0; $nonSov = 0.0;
        foreach ($rows as $h) {
            $w = (float) $h->pct_nav / 100;
            $name = (string) $h->instrument;
            $mat = $h->maturity_on ?? ($h->isin ? ($known[$h->isin] ?? null) : null);
            $years = $mat ? max(1 / 365, Carbon::parse($mat)->floatDiffInYears($asOf, true)) : null;
            if ($mat && Carbon::parse($mat)->lt($asOf)) $years = 1 / 365;
            $rating = null;
            if (preg_match('/\bCDMDF\b|corporate debt market development/i', $name)) {
                if ($cfg['cdmdf_dislocation']) {
                    $a3 = (bool) preg_match('/\bA3\b/', $name);
                    $rating = $a3 ? $this->notch($cfg['cdmdf_dislocation_rating'], (int) $cfg['cdmdf_a3_notch']) : $cfg['cdmdf_dislocation_rating'];
                    $end = preg_match('~(\d{1,2})/(\d{1,2})/(\d{4})~', $name, $d) ? Carbon::create((int) $d[3], (int) $d[2], (int) $d[1]) : null;
                    $years = $a3 ? (float) $cfg['cdmdf_a3_years'] : ($end ? max(1 / 365, $end->floatDiffInYears($asOf, true)) : 3.0);
                } else {
                    $rating = 'AAA'; $years = 7 / 365;   // normal times: G-Secs, T-bills and repo of up to seven days
                }
                $flags['cdmdf'] = round(($flags['cdmdf'] ?? 0) + $w * 100, 2);
            } elseif (preg_match('/\binvit\b|infrastructure investment trust|infra(structure)? trust/i', $name)) {
                $base = $h->asset_type === 'long' && $h->rating ? $h->rating : $cfg['unrated_as'];
                $rating = $this->notch($base, (int) $cfg['invit_notch']);
                $years = (float) $cfg['invit_years'];
                $flags['invit'][] = $name;
            } else {
                $rating = match ($h->asset_type) {
                    'long' => $h->rating,
                    'unrated' => $cfg['unrated_as'],
                    default => null,
                };
                if ($h->asset_type === 'short') {
                    // Short-term-only paper: the issuer's own long-term rating where one exists, else the lowest long-term
                    // rating the short-term grade maps to on the rating scale.
                    $issuerLong = ($h->issuer_key ? ($byIssuer[$h->issuer_key] ?? null) : null) ?? ($byName[\App\Models\Company::nameKey($name)] ?? null);
                    $how = $issuerLong ? 'short_issuer_rating' : (isset($view[$h->rating]) ? 'short_internal_view' : 'short_mapped');
                    $rating = $issuerLong ?? $view[$h->rating] ?? ($cfg['short_lowest_long'][$h->rating] ?? 'BBB-');
                    $flags[$how] = round(($flags[$how] ?? 0) + $w * 100, 2);
                }
                if ($h->asset_type === 'unrated') $flags['unrated'][] = $name;
            }
            $debt = in_array($h->asset_type, ['long', 'short', 'unrated', 'sovereign', 'cash'], true) || $rating !== null;
            if (!$debt) continue;
            if ($years === null) {
                if (in_array($h->asset_type, ['long', 'unrated', 'sovereign'], true)) { $years = 3.0; $assumed++; }
                else $years = $h->asset_type === 'short' ? 0.25 : 0.0;
            }
            if ($rating !== null) { $score += $w * $this->secScore($rating, $years); $nonSov += $w; }
            $wSum += $w; $wYears += $w * $years; $maxYears = max($maxYears, $years);
        }
        if ($assumed) $flags['assumed_maturity'] = $assumed;
        foreach (['invit', 'unrated'] as $k) if (isset($flags[$k])) $flags[$k] = array_slice(array_values(array_unique($flags[$k])), 0, 6);
        return ['score' => round($score, 4), 'wam' => round($wSum > 0 ? $wYears / $wSum : 0, 3), 'flags' => $flags,
            'short_eligible' => $wSum > 0 && $maxYears <= 1 && !$assumed, 'gilt_only' => $nonSov < 0.0001];
    }

    /**
     * Highest rating whose benchmark the fund score does not exceed. ICRA's matrix sets each benchmark between grades
     * (AAA 12 and AA+ 16 at six years; a score of 13 rates AA+), so the benchmark at maturity $t is the geometric midpoint
     * of that rating's score and the next lower rating's score.
     */
    private function implied(float $score, float $t): array
    {
        foreach (self::LONG as $i => $r) {
            $next = self::LONG[$i + 1] ?? null;
            if (!$next) break;
            $b = sqrt(max($this->secScore($r, $t), 1e-9) * $this->secScore($next, $t));
            if ($score <= $b) return [$r, round($b, 4)];
        }
        return ['D', null];
    }

    /** Three-month rule, curing month, sharp breach and the three-month upgrade test. @return array{0:string,1:string} */
    private function publish(string $key, string $asOf, string $implied, bool $giltOnly): array
    {
        $cfg = $this->cfg();
        $idx = fn ($r) => array_search($r, self::LONG, true);
        $hist = DB::table('mf_icra_credit_quality')->where('scheme_key', $key)->where('as_of', '<', $asOf)->orderByDesc('as_of')->limit(12)->get();
        $prev = $hist->first();
        $look = max(1, (int) $cfg['lookback_months']);
        $recent = collect([$implied])->merge($hist->take($look - 1)->pluck('implied'))->filter();
        $worst = $recent->sortByDesc($idx)->first();
        if (!$prev || $prev->status === 'provisional') {
            // Initial assignment: every one of the last three month-ends must support the rating.
            $final = $giltOnly || $recent->count() >= $look;
            return [$worst, $final ? 'final' : 'provisional'];
        }
        $current = $prev->rating;
        if ($idx($implied) > $idx($current)) {
            if ($idx($implied) - $idx($current) >= (int) $cfg['sharp_breach_notches']) return [$implied, 'downgraded'];
            return $prev->status === 'curing' ? [$implied, 'downgraded'] : [$current, 'curing'];
        }
        if ($idx($implied) < $idx($current)) {
            $need = max(1, (int) $cfg['upgrade_months']);
            $run = collect([$implied])->merge($hist->take($need - 1)->pluck('implied'));
            if ($run->count() >= $need && $run->every(fn ($r) => $r && $idx($r) < $idx($current))) return [$run->sortByDesc($idx)->first(), 'upgraded'];
            return [$current, 'upgrade_pending'];
        }
        return [$current, 'final'];
    }

    /** SEBI category of each scheme from the scheme master (text after " - "), else from its name. */
    private function categories(array $names): array
    {
        $master = DB::table('mf_schemes')->whereNotNull('category')->get(['name', 'category'])
            ->mapWithKeys(fn ($s) => [PortfolioParser::key($s->name) => trim(preg_replace('/^.*\s-\s/', '', $s->category))]);
        $out = [];
        foreach ($names as $key => $name) {
            $cat = $master[$key] ?? null;
            if (!$cat) foreach (['Overnight Fund' => 'overnight', 'Liquid Fund' => 'liquid', 'Money Market Fund' => 'money market', 'Ultra Short Duration Fund' => 'ultra short',
                'Low Duration Fund' => 'low duration', 'Short Duration Fund' => 'short (term|duration)', 'Medium to Long Duration Fund' => 'medium to long', 'Medium Duration Fund' => 'medium (term|duration)',
                'Long Duration Fund' => 'long (term|duration)', 'Corporate Bond Fund' => 'corporate bond', 'Credit Risk Fund' => 'credit risk', 'Banking and PSU Fund' => 'banking (and|&) psu',
                'Gilt Fund' => 'gilt', 'Floater Fund' => 'float', 'Dynamic Bond' => 'dynamic bond'] as $c => $re) if (preg_match('/' . $re . '/i', (string) $name)) { $cat = $c; break; }
            $out[$key] = $cat;
        }
        return $out;
    }

    /** Weakest latest rating among the AMC's short-duration debt schemes: the anchor for its bank borrowing / intraday lines. */
    public static function lineAnchor(string $amc): ?object
    {
        return rescue(function () use ($amc) {
            $latest = DB::table('mf_icra_credit_quality')->where('amc', $amc)->max('as_of');
            if (!$latest) return null;
            $rows = DB::table('mf_icra_credit_quality')->where('amc', $amc)->where('as_of', $latest)->whereIn('category', self::LINE_CATEGORIES)->get();
            return $rows->sortByDesc(fn ($r) => array_search($r->rating, self::LONG, true))->first();
        }, null, false);
    }

    public static function forScheme(string $name): ?object
    {
        $k = PortfolioParser::key($name);
        return rescue(fn () => DB::table('mf_icra_credit_quality')->where('scheme_key', $k)->orderByDesc('as_of')->first(), null, false);
    }

    private function secScore(string $rating, float $years): float
    {
        $p = min(100, (float) ($this->pd()[$rating] ?? 100)) / 100;
        return (1 - (1 - $p) ** max($years, 1 / 365)) * 10000;
    }

    private function notch(string $rating, int $n): string
    {
        $i = array_search($rating, self::LONG, true);
        return $i === false ? $rating : self::LONG[min(count(self::LONG) - 1, $i + $n)];
    }
}
