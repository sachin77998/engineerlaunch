<?php

namespace App\Services\Funds;

use App\Services\Insights\Methodology;
use App\Services\Insights\RatingRationaleParser;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fund credit quality (CQR) per the Fund Credit Quality rating methodology:
 *  - each security gets a credit score from its rating and residual maturity (cumulative default probability, bps);
 *    short-term ratings use their long-term equivalent, unrated paper an internal view, watch / negative one notch lower;
 *  - single-issuer exposure above the SEBI limits (10% AAA, 8% AA, 6% A and below) is scored one notch lower;
 *  - the fund score is the NAV-weighted sum; a category's benchmark is the geometric midpoint between that rating's
 *    score and the next lower rating's score at the fund's weighted average maturity;
 *  - a month that breaches the published band gets one month's curing before the band changes, and an upgrade needs
 *    the worst of the last three months to support it; funds holding only sub-one-year paper also get a short-term band.
 */
class FundCreditQuality
{
    private const LONG = ['AAA', 'AA+', 'AA', 'AA-', 'A+', 'A', 'A-', 'BBB+', 'BBB', 'BBB-', 'BB+', 'BB', 'BB-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D'];

    public function __construct(private Methodology $m) {}

    /** Scores every scheme in a parsed file and stores the result. Returns schemes scored. */
    public function scoreFile(int $fileId, array $aliases = []): int
    {
        $n = 0;
        $groups = DB::table('mf_portfolio_holdings')->where('file_id', $fileId)->get()->groupBy('scheme_key');
        foreach ($groups as $key => $rows) if ($this->store($key, $rows, $fileId, $aliases[$key] ?? null)) $n++;
        return $n;
    }

    public function store(string $key, Collection $rows, ?int $fileId = null, ?string $aliasKey = null): ?array
    {
        $r = $this->score($rows);
        if (!$r) return null;
        $first = $rows->first();
        $asOf = Carbon::parse($first->as_of);
        [$published, $curing] = $this->publish($key, $asOf, $r['implied']);
        $short = $r['short_eligible'] ? ($this->m->setting('short_term_map', [])[$published][0] ?? null) : null;
        DB::table('mf_credit_quality')->updateOrInsert(['scheme_key' => $key, 'as_of' => $asOf->toDateString()], [
            'amc' => $first->amc, 'scheme_name' => $first->scheme_name, 'alias_key' => $aliasKey,
            'debt_share' => $r['debt_share'], 'credit_score' => $r['score'], 'benchmark_score' => $r['benchmark'], 'wam_years' => $r['wam'],
            'implied' => $r['implied'], 'cqr' => $published, 'cqr_short' => $short, 'curing' => $curing,
            'distribution' => json_encode($r['distribution']), 'concentration' => json_encode($r['concentration'], JSON_UNESCAPED_UNICODE), 'flags' => json_encode($r['flags'], JSON_UNESCAPED_UNICODE),
            'holdings' => $rows->count(), 'file_id' => $fileId ?? $first->file_id, 'updated_at' => now(), 'created_at' => now(),
        ]);
        return $r + ['cqr' => $published, 'curing' => $curing, 'cqr_short' => $short];
    }

    /** The scoring itself (no storage), for one scheme's holdings. */
    public function score(Collection $rows): ?array
    {
        $rules = ($this->m->setting('fund_rules', []) ?: []) + config('fund_portfolios.fund_rules');
        $pd = ($this->m->setting('fund_pd_annual', []) ?: []) + config('fund_portfolios.fund_pd_annual');
        $shortEq = ($this->m->setting('fund_short_equivalent', []) ?: []) + config('fund_portfolios.fund_short_equivalent');
        $asOf = Carbon::parse($rows->first()->as_of);
        $total = $rows->sum('pct_nav');
        $debtLike = $rows->whereIn('asset_type', ['long', 'short', 'unrated', 'sovereign', 'cash'])->sum('pct_nav');
        if ($total < 80 || $debtLike < (float) $rules['min_debt_share']) return null;
        // Commodity ETFs and FoFs hold metal or units, not debt; credit quality does not apply.
        if (preg_match('/\b(gold|silver|commodit\w*)\b/i', (string) $rows->first()->scheme_name)) return null;

        // Missing maturities: the same ISIN in any other portfolio, else an assumed tenor.
        $known = DB::table('mf_portfolio_holdings')->whereIn('isin', $rows->whereNull('maturity_on')->pluck('isin')->filter()->unique())->whereNotNull('maturity_on')
            ->orderByDesc('as_of')->pluck('maturity_on', 'isin');
        $assumed = 0;
        $flags = [];
        $items = $rows->map(function ($h) use ($known, $asOf, &$assumed, $shortEq, $rules, &$flags) {
            $mat = $h->maturity_on ?? ($h->isin ? ($known[$h->isin] ?? null) : null);
            if (!$mat && in_array($h->asset_type, ['long', 'unrated', 'sovereign'], true)) { $assumed++; $years = 3.0; }
            else $years = $mat ? max(1 / 365, Carbon::parse($mat)->floatDiffInYears($asOf, true)) : ($h->asset_type === 'short' ? 0.25 : 0);
            if ($mat && Carbon::parse($mat)->lt($asOf)) $years = 1 / 365;
            $rating = match ($h->asset_type) {
                'long' => $h->rating, 'short' => $shortEq[$h->rating] ?? 'BBB', 'unrated' => $rules['unrated_as'], default => null,
            };
            if ($rating && preg_match('/watch|rwn|negative|credit watch/i', (string) $h->rating_raw)) { $rating = $this->notch($rating, (int) $rules['watch_notch']); $flags['watch'][] = $h->instrument; }
            if ($h->asset_type === 'unrated') $flags['unrated'][] = $h->instrument;
            return ['h' => $h, 'years' => $years, 'rating' => $rating, 'w' => (float) $h->pct_nav / 100];
        });
        if ($assumed) $flags['assumed_maturity'] = $assumed;

        // Single-issuer limits by the issuer's weakest rating band.
        $limitFor = fn ($r) => in_array($r, ['AAA'], true) ? $rules['limit_aaa'] : (in_array($r, ['AA+', 'AA', 'AA-'], true) ? $rules['limit_aa'] : $rules['limit_a_below']);
        $conc = [];
        foreach ($items->filter(fn ($i) => $i['rating'] !== null)->groupBy(fn ($i) => $i['h']->issuer_key) as $issuer => $g) {
            $exp = $g->sum('w') * 100;
            $worst = $g->sortByDesc(fn ($i) => array_search($i['rating'], self::LONG, true))->first()['rating'];
            $limit = (float) $limitFor($worst);
            if ($exp > $limit) $conc[] = ['issuer' => $this->issuerName($g->first()['h']->instrument), 'exposure' => round($exp, 2), 'limit' => $limit, 'rating' => $worst, 'excess_share' => ($exp - $limit) / $exp, 'key' => $issuer];
        }
        $excess = collect($conc)->mapWithKeys(fn ($c) => [$c['key'] => $c['excess_share']]);

        $score = 0.0;
        foreach ($items as $i) {
            if ($i['rating'] === null) continue;
            $s = $this->secScore($i['rating'], $i['years'], $pd);
            $ex = $excess[$i['h']->issuer_key] ?? 0;
            if ($ex > 0) $s = $s * (1 - $ex) + $this->secScore($this->notch($i['rating'], (int) $rules['excess_notch']), $i['years'], $pd) * $ex;
            $score += $i['w'] * $s;
        }
        $debtW = $items->filter(fn ($i) => in_array($i['h']->asset_type, ['long', 'short', 'unrated', 'sovereign', 'cash'], true));
        $wam = $debtW->sum('w') > 0 ? $debtW->sum(fn ($i) => $i['w'] * $i['years']) / $debtW->sum('w') : 0;
        $t = max($wam, 1 / 12);
        $implied = 'D'; $benchmark = null;
        foreach (self::LONG as $idx => $r) {
            $next = self::LONG[$idx + 1] ?? null;
            if (!$next) break;
            $b = sqrt(max($this->secScore($r, $t, $pd), 1e-6) * $this->secScore($next, $t, $pd));
            if ($score <= $b) { $implied = $r; $benchmark = $b; break; }
        }
        $band = fn ($i) => match (true) {
            in_array($i['h']->asset_type, ['sovereign', 'cash'], true) => 'Sovereign & cash',
            $i['rating'] === 'AAA' || ($i['h']->asset_type === 'short' && $i['h']->rating === 'A1+') => 'AAA / A1+',
            in_array($i['rating'], ['AA+', 'AA', 'AA-'], true) => 'AA',
            in_array($i['rating'], ['A+', 'A', 'A-'], true) => 'A',
            $i['h']->asset_type === 'unrated' => 'Unrated',
            $i['rating'] !== null => 'BBB & below',
            default => 'Other',
        };
        $dist = $items->groupBy($band)->map(fn ($g) => round($g->sum('w') * 100, 2))->sortDesc()->all();
        $maxYears = $debtW->max('years');
        return [
            'score' => round($score, 4), 'benchmark' => $benchmark !== null ? round($benchmark, 4) : null, 'wam' => round($wam, 3), 'implied' => $implied,
            'debt_share' => round($debtLike, 2), 'distribution' => $dist, 'concentration' => array_map(fn ($c) => array_diff_key($c, ['excess_share' => 1, 'key' => 1]), $conc),
            'flags' => array_map(fn ($v) => is_array($v) ? array_slice(array_values(array_unique($v)), 0, 10) : $v, $flags),
            'short_eligible' => $maxYears !== null && $maxYears <= (float) $rules['short_term_max_years'] && $assumed === 0,
        ];
    }

    /** One month's curing on a breach; upgrades only when the worst of the last three months supports them. */
    private function publish(string $key, Carbon $asOf, string $implied): array
    {
        $rules = ($this->m->setting('fund_rules', []) ?: []) + config('fund_portfolios.fund_rules');
        $hist = DB::table('mf_credit_quality')->where('scheme_key', $key)->where('as_of', '<', $asOf->toDateString())->orderByDesc('as_of')->limit(max(1, (int) $rules['lookback_months'] - 1))->get();
        $prev = $hist->first();
        $idx = fn ($r) => array_search($r, self::LONG, true);
        if (!$prev || !$prev->cqr) return [$implied, false];
        if ($idx($implied) > $idx($prev->cqr)) return $prev->curing ? [$implied, false] : [$prev->cqr, true];
        $worst = collect([$implied])->merge($hist->pluck('implied'))->filter()->sortByDesc($idx)->first();
        return [$idx($worst) < $idx($prev->cqr) ? $worst : $prev->cqr, false];
    }

    private function secScore(string $rating, float $years, array $pd): float
    {
        $p = min(100, (float) ($pd[$rating] ?? 100)) / 100;
        return (1 - (1 - $p) ** max($years, 1 / 365)) * 10000;
    }

    private function notch(string $rating, int $n): string
    {
        $i = array_search($rating, self::LONG, true);
        return $i === false ? $rating : self::LONG[min(count(self::LONG) - 1, $i + $n)];
    }

    private function issuerName(string $instrument): string
    {
        $n = preg_replace('/^\s*\d+(\.\d+)?\s*%\s*/', '', $instrument);
        $n = preg_replace('/\s*[-–]\s*\d{1,2}\/\d{1,2}\/\d{2,4}.*$|\s*\(.*$|\*+/', '', $n);
        return trim(mb_substr($n, 0, 60));
    }

    /** Latest stored credit quality for a scheme-master name. */
    public static function forScheme(string $name): ?object
    {
        $k = PortfolioParser::key($name);
        return rescue(fn () => DB::table('mf_credit_quality')->where(fn ($q) => $q->where('scheme_key', $k)->orWhere('alias_key', $k))->orderByDesc('as_of')->first(), null, false);
    }
}
