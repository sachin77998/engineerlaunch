<?php
namespace App\Services\Insights;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "Similar companies" recommender, rebuilt by a nightly batch. A hybrid of three signals:
 *  1. Content: TF-IDF vectors over industry, sector, sector-directory categories, products, brands and activity text;
 *     cosine similarity, with candidates drawn from an inverted index on each company's strongest terms.
 *  2. Financial profile: standardised (z-score) revenue scale, EBITDA and PAT margins, revenue growth, leverage and
 *     credit-rating score; similarity = exp(-squared distance / 2 per shared dimension).
 *  3. Collaborative: companies opened by the same visitors (item-item co-occurrence, cosine-normalised).
 * score = 0.55 × content + 0.25 × financial + 0.20 × viewed-together (+0.05 same state); top 12 stored per company.
 */
class CompanySimilarity
{
    private const STOP = ['and', 'the', 'for', 'with', 'from', 'other', 'others', 'services', 'service', 'limited', 'private', 'india', 'indian', 'company', 'products', 'product', 'its', 'are', 'all', 'manufacturing', 'manufacture', 'related', 'activities', 'activity', 'industries', 'industry', 'group', 'solutions', 'ltd', 'pvt', 'inc', 'etc', 'including', 'which', 'this', 'that'];

    public function build(int $topK = 12, int $max = 25000, ?callable $log = null): int
    {
        $docs = $this->documents($max);
        $n = count($docs);
        if ($n < 2) return 0;
        $log && $log("Companies in the model: $n");

        // TF-IDF with L2 normalisation; keep each company's 30 strongest terms.
        $df = [];
        foreach ($docs as $d) foreach (array_keys($d['tf']) as $t) $df[$t] = ($df[$t] ?? 0) + 1;
        $vectors = []; $index = [];
        $common = max(50, (int) ($n * 0.12)); // terms in more than ~12% of companies are too common to propose candidates
        foreach ($docs as $id => $d) {
            $v = [];
            foreach ($d['tf'] as $t => $tf) $v[$t] = (1 + log($tf)) * log(($n + 1) / ($df[$t] + 1)) * ($d['boost'][$t] ?? 1);
            arsort($v); $v = array_slice($v, 0, 30, true);
            $norm = sqrt(array_sum(array_map(fn ($x) => $x * $x, $v))) ?: 1;
            foreach ($v as $t => $w) $v[$t] = $w / $norm;
            $vectors[$id] = $v;
            foreach (array_slice($v, 0, 12, true) as $t => $w) if ($df[$t] <= $common && $df[$t] > 1) $index[$t][] = $id;
        }
        $numeric = $this->numeric(array_keys($docs));
        $coviews = $this->coviews(array_keys($docs));
        $now = now();
        $written = 0;
        $batch = [];
        foreach ($vectors as $id => $v) {
            $candidates = [];
            foreach (array_slice($v, 0, 12, true) as $t => $w) foreach (array_slice($index[$t] ?? [], 0, 3000) as $other) if ($other !== $id) $candidates[$other] = true;
            foreach (array_keys($coviews[$id] ?? []) as $other) $candidates[$other] = true;
            if (count($candidates) > 4000) $candidates = array_slice($candidates, 0, 4000, true);
            $scores = [];
            foreach (array_keys($candidates) as $other) {
                $text = 0.0;
                foreach ($v as $t => $w) if (isset($vectors[$other][$t])) $text += $w * $vectors[$other][$t];
                $fin = $this->numericSimilarity($numeric[$id] ?? null, $numeric[$other] ?? null);
                $co = $coviews[$id][$other] ?? 0.0;
                $state = ($docs[$id]['state'] && $docs[$id]['state'] === $docs[$other]['state']) ? 0.05 : 0.0;
                // Small tie-break towards companies with financial data, which have fuller pages.
                $score = 0.55 * $text + 0.25 * $fin + 0.20 * $co + $state + (isset($numeric[$other]) ? 0.02 : 0.0);
                if ($score > 0.08) $scores[$other] = [$score, $text, $fin, $co];
            }
            uasort($scores, fn ($a, $b) => $b[0] <=> $a[0]);
            foreach (array_slice($scores, 0, $topK, true) as $other => [$score, $text, $fin, $co]) {
                $batch[] = ['company_id' => $id, 'similar_company_id' => $other, 'score' => round($score, 4),
                    'reasons' => json_encode($this->reasons($docs[$id], $docs[$other], $v, $vectors[$other], $numeric[$id] ?? null, $numeric[$other] ?? null, $fin, $co, $coviews['_n'][$id][$other] ?? 0)),
                    'created_at' => $now, 'updated_at' => $now];
            }
            if (count($batch) >= 1000) { $written += $this->flush($batch); $batch = []; }
        }
        $written += $this->flush($batch);
        // Drop rows for companies left out of this build.
        DB::table('company_similarities')->where('updated_at', '<', $now)->delete();
        $log && $log("Stored $written similar-company links.");
        return $written;
    }

    private function flush(array $rows): int
    {
        if (!$rows) return 0;
        DB::table('company_similarities')->upsert($rows, ['company_id', 'similar_company_id'], ['score', 'reasons', 'updated_at']);
        return count($rows);
    }

    /** Companies worth recommending (with financials, ratings, registry data, sector categories or recent views) and their terms. */
    private function documents(int $max): array
    {
        $ids = DB::table('company_financial_statements')->distinct()->pluck('company_id')
            ->merge(DB::table('credit_ratings')->whereNotNull('company_id')->distinct()->pluck('company_id'))
            ->merge(DB::table('company_registry_details')->pluck('company_id'))
            ->merge(DB::table('company_page_views')->where('viewed_on', '>=', now()->subDays(90))->distinct()->pluck('company_id'))
            ->merge(DB::table('company_category_company')->distinct()->limit($max)->pluck('company_id'))
            ->unique()->take($max)->values();
        $categories = DB::table('company_category_company as cc')->join('company_categories as c', 'c.id', '=', 'cc.company_category_id')
            ->whereIn('cc.company_id', $ids)->select('cc.company_id', 'c.name')->get()->groupBy('company_id');
        $registry = DB::table('company_registry_details')->whereIn('company_id', $ids)->get(['company_id', 'principal_activity', 'city'])->keyBy('company_id');
        $docs = [];
        foreach ($ids->chunk(2000) as $chunk) {
            foreach (Company::whereIn('id', $chunk)->where('is_active', true)->get(['id', 'name', 'industry', 'sector', 'products', 'brands', 'description', 'registered_state', 'country']) as $c) {
                $tf = []; $boost = [];
                $add = function (?string $text, float $weight) use (&$tf, &$boost) {
                    foreach ($this->tokens((string) $text) as $t) { $tf[$t] = ($tf[$t] ?? 0) + 1; $boost[$t] = max($boost[$t] ?? 0, $weight); }
                };
                $phrase = function (?string $text, float $weight) use (&$tf, &$boost) {
                    $key = 'cat:' . trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower((string) $text)));
                    if ($key === 'cat:') return;
                    $tf[$key] = ($tf[$key] ?? 0) + 1; $boost[$key] = max($boost[$key] ?? 0, $weight);
                };
                $phrase($c->industry, 2.0); $phrase($c->sector, 2.0);
                $add($c->industry, 1.5); $add($c->sector, 1.5);
                foreach ($categories[$c->id] ?? [] as $cat) { $phrase($cat->name, 1.8); $add($cat->name, 1.2); }
                $add(is_array($c->products) ? implode(' ', $c->products) : $c->products, 1.0);
                $add(is_array($c->brands) ? implode(' ', $c->brands) : (string) $c->brands, 0.6);
                $add(mb_substr((string) $c->description, 0, 600), 0.6);
                $add($registry[$c->id]->principal_activity ?? null, 1.0);
                if (!$tf) continue;
                $docs[$c->id] = ['tf' => $tf, 'boost' => $boost, 'state' => $c->registered_state ?: ($registry[$c->id]->city ?? null), 'name' => $c->name,
                    'labels' => array_values(array_filter([$c->sector, $c->industry, ...collect($categories[$c->id] ?? [])->pluck('name')->take(3)->all()]))];
            }
        }
        return $docs;
    }

    private function tokens(string $text): array
    {
        $text = strtolower(preg_replace('/[^A-Za-z0-9 ]+/', ' ', $text));
        $out = [];
        foreach (preg_split('/\s+/', $text) as $w) {
            if (strlen($w) < 3 || is_numeric($w) || in_array($w, self::STOP, true)) continue;
            $out[] = preg_replace('/(ies)$/', 'y', preg_replace('/(?<=[a-z]{4})s$/', '', $w)); // light stemming: plurals
        }
        return $out;
    }

    /** Latest-year financial features (z-scored) and rating score per company. */
    private function numeric(array $ids): array
    {
        $rows = [];
        foreach (array_chunk($ids, 2000) as $chunk) {
            $stmts = DB::table('company_financial_statements')->whereIn('company_id', $chunk)->orderBy('financial_year')
                ->get(['company_id', 'financial_year', 'revenue', 'ebitda', 'profit_after_tax', 'total_debt', 'net_worth'])->groupBy('company_id');
            foreach ($stmts as $cid => $list) {
                $last = $list->last(); $prev = $list->count() > 1 ? $list->slice(-2, 1)->first() : null;
                $rev = (float) $last->revenue;
                $f = [];
                if ($rev > 0) {
                    $f['scale'] = log10($rev);
                    if ($last->ebitda !== null) $f['ebitda_margin'] = max(-1, min(1, $last->ebitda / $rev));
                    if ($last->profit_after_tax !== null) $f['pat_margin'] = max(-1, min(1, $last->profit_after_tax / $rev));
                    if ($prev && (float) $prev->revenue > 0) $f['growth'] = max(-1, min(2, $rev / $prev->revenue - 1));
                }
                if ((float) $last->net_worth > 0 && $last->total_debt !== null) $f['leverage'] = min(10, $last->total_debt / $last->net_worth);
                if ($f) $rows[$cid] = $f;
            }
            foreach (DB::table('credit_ratings')->whereIn('company_id', $chunk)->whereNotNull('score')->where('withdrawn', false)->orderBy('published_on')->get(['company_id', 'score']) as $r) {
                $rows[$r->company_id]['rating'] = (float) $r->score;
            }
        }
        // Standardise each feature across companies.
        $stats = [];
        foreach (['scale', 'ebitda_margin', 'pat_margin', 'growth', 'leverage', 'rating'] as $k) {
            $vals = array_values(array_filter(array_column($rows, $k), fn ($v) => $v !== null));
            if (count($vals) < 3) continue;
            $mean = array_sum($vals) / count($vals);
            $sd = sqrt(array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $vals)) / count($vals)) ?: 1;
            $stats[$k] = [$mean, $sd];
        }
        foreach ($rows as $cid => $f) {
            $z = ['raw' => $f];
            foreach ($f as $k => $v) if (isset($stats[$k])) $z[$k] = ($v - $stats[$k][0]) / $stats[$k][1];
            $rows[$cid] = $z;
        }
        return $rows;
    }

    private function numericSimilarity(?array $a, ?array $b): float
    {
        if (!$a || !$b) return 0.0;
        $d = 0.0; $k = 0;
        foreach ($a as $key => $v) {
            if ($key === 'raw' || !isset($b[$key])) continue;
            $d += ($v - $b[$key]) ** 2; $k++;
        }
        return $k ? exp(-$d / (2 * $k)) * min(1, $k / 3) : 0.0;
    }

    /** Item-item co-occurrence from visitors' company page views (last 90 days), cosine-normalised. */
    private function coviews(array $ids): array
    {
        $allowed = array_flip($ids);
        $sessions = DB::table('company_page_views')->where('viewed_on', '>=', now()->subDays(90))->orderBy('visitor')->get(['visitor', 'company_id'])->groupBy('visitor');
        $pairs = []; $counts = [];
        foreach ($sessions as $views) {
            $set = $views->pluck('company_id')->unique()->filter(fn ($c) => isset($allowed[$c]))->take(40)->values()->all();
            foreach ($set as $c) $counts[$c] = ($counts[$c] ?? 0) + 1;
            $m = count($set);
            for ($i = 0; $i < $m; $i++) for ($j = $i + 1; $j < $m; $j++) {
                $pairs[$set[$i]][$set[$j]] = ($pairs[$set[$i]][$set[$j]] ?? 0) + 1;
                $pairs[$set[$j]][$set[$i]] = ($pairs[$set[$j]][$set[$i]] ?? 0) + 1;
            }
        }
        $out = ['_n' => []];
        foreach ($pairs as $a => $list) foreach ($list as $b => $n) {
            if ($n < 2) continue; // a single shared visitor is noise
            $out[$a][$b] = $n / sqrt($counts[$a] * $counts[$b]);
            $out['_n'][$a][$b] = $n;
        }
        return $out;
    }

    private function reasons(array $a, array $b, array $va, array $vb, ?array $na, ?array $nb, float $fin, float $co, int $coCount): array
    {
        $reasons = [];
        $shared = collect(array_intersect_key($va, $vb))->map(fn ($w, $t) => $w * $vb[$t])->sortDesc()->keys();
        $phrase = $shared->first(fn ($t) => str_starts_with($t, 'cat:'));
        if ($phrase) {
            $label = collect($a['labels'])->first(fn ($l) => 'cat:' . trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($l))) === $phrase);
            $reasons[] = 'Same segment: ' . ($label ?? ucwords(substr($phrase, 4)));
        } elseif ($shared->isNotEmpty()) {
            $reasons[] = 'Shared focus: ' . $shared->reject(fn ($t) => str_starts_with($t, 'cat:'))->take(3)->implode(', ');
        }
        if ($na && $nb && isset($na['raw']['scale'], $nb['raw']['scale']) && abs($na['raw']['scale'] - $nb['raw']['scale']) < 0.35) $reasons[] = 'Similar revenue scale';
        if ($na && $nb && isset($na['raw']['rating'], $nb['raw']['rating']) && abs($na['raw']['rating'] - $nb['raw']['rating']) <= 1) $reasons[] = 'Similar credit rating';
        if ($fin > 0.6 && count($reasons) < 3) $reasons[] = 'Close financial profile';
        if ($coCount >= 2) $reasons[] = "Viewed together by $coCount visitors";
        if ($a['state'] && $a['state'] === $b['state']) $reasons[] = 'Same location: ' . $a['state'];
        return array_slice($reasons, 0, 3);
    }

    /** Stored recommendations for a company page (falls back to nothing until the first build). */
    public function for(Company $company, int $limit = 8): Collection
    {
        return DB::table('company_similarities as s')->join('companies as c', 'c.id', '=', 's.similar_company_id')
            ->where('s.company_id', $company->id)->where('c.is_active', true)->orderByDesc('s.score')->limit($limit)
            ->get(['c.id', 'c.name', 'c.slug', 'c.logo_url', 'c.industry', 's.score', 's.reasons'])
            ->map(function ($r) { $r->reasons = json_decode((string) $r->reasons, true) ?: []; return $r; });
    }

    /** "Recommended for you": neighbours of the companies a visitor opened recently, ranked by summed score. */
    public function forVisitor(array $recentSlugs, int $limit = 8): Collection
    {
        if (!$recentSlugs) return collect();
        $seen = Company::whereIn('slug', $recentSlugs)->pluck('id');
        if ($seen->isEmpty()) return collect();
        return DB::table('company_similarities as s')->join('companies as c', 'c.id', '=', 's.similar_company_id')
            ->whereIn('s.company_id', $seen)->whereNotIn('s.similar_company_id', $seen)->where('c.is_active', true)
            ->groupBy('c.id', 'c.name', 'c.slug', 'c.logo_url', 'c.industry')
            ->orderByRaw('SUM(s.score) DESC')->limit($limit)
            ->get(['c.id', 'c.name', 'c.slug', 'c.logo_url', 'c.industry', DB::raw('SUM(s.score) as score'), DB::raw('COUNT(*) as hits')]);
    }

    /** Records a company page view for the "viewed together" signal (one row per visitor, company and day). */
    public static function recordView(int $companyId, string $visitor): void
    {
        try {
            DB::table('company_page_views')->insertOrIgnore(['company_id' => $companyId, 'visitor' => substr($visitor, 0, 64), 'viewed_on' => now()->toDateString()]);
        } catch (\Throwable) {
        }
    }
}
