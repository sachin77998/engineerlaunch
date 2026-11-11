<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\FundingRound;
use App\Models\Investor;
use App\Models\NewsArticle;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns funding news into rounds and investors. Several outlets report the same round, so an article joins an
 * existing round of the same company within 45 days when the round type or the amount agrees; it then adds its
 * investors and fills what the earlier headline left out.
 */
class FundingRoundImporter
{
    private const CURSOR = 'funding-rounds:last-article-id';
    private const MERGE_DAYS = 45;

    public function __construct(private FundingRoundExtractor $extractor) {}

    /** @return array{scanned:int, rounds:int, merged:int} */
    public function run(bool $all = false, int $limit = 5000, ?callable $log = null): array
    {
        $from = $all ? 0 : (int) cache()->get(self::CURSOR, 0);
        $stats = ['scanned' => 0, 'rounds' => 0, 'merged' => 0];
        $touched = [];
        // Keyset batches of 500 (only headlines with a funding word are read), oldest first so merges keep the earliest date.
        while ($stats['scanned'] < $limit) {
            $articles = NewsArticle::query()->where('id', '>', $from)
                ->where(fn ($q) => collect(['%rais%', '%fund%', '%series%', '%seed%', '%led by%', '%invest%', '%secure%', '%bag%', '%round%'])->each(fn ($w) => $q->orWhere('title', 'like', $w)))
                ->orderBy('id')->limit(min(500, $limit - $stats['scanned']))
                ->get(['id', 'title', 'excerpt', 'raw_content', 'source_url', 'source_published_at', 'published_at', 'created_at']);
            if ($articles->isEmpty()) break;
            foreach ($articles as $article) {
                $stats['scanned']++;
                $found = $this->extractor->extract((string) $article->title, (string) ($article->excerpt ?: $article->raw_content));
                if (!$found) continue;
                [$round, $merged] = $this->store($article, $found);
                $stats[$merged ? 'merged' : 'rounds']++;
                foreach ($round->investors()->pluck('investors.id') as $id) $touched[$id] = true;
                $log && $log(($merged ? '  + ' : '  ') . $found['company'] . ' · ' . $round->label() . ($round->amountLabel() ? ' · ' . $round->amountLabel() : ''));
            }
            $from = $articles->max('id');
            cache()->forever(self::CURSOR, $from);
        }
        $this->recount(array_keys($touched));
        return $stats;
    }

    /** @return array{0: FundingRound, 1: bool} the round and whether the article was merged into an existing one */
    private function store(NewsArticle $article, array $found): array
    {
        return DB::transaction(function () use ($article, $found) {
            $key = Company::nameKey($found['company']);
            $on = Carbon::parse($article->source_published_at ?? $article->published_at ?? $article->created_at)->startOfDay();
            $round = FundingRound::where('company_key', $key)->whereBetween('announced_on', [$on->copy()->subDays(self::MERGE_DAYS), $on->copy()->addDays(self::MERGE_DAYS)])
                ->get()->first(fn (FundingRound $r) => $r->round_type === $found['round_type'] || $r->round_type === 'undisclosed' || $found['round_type'] === 'undisclosed'
                    || ($r->amount_usd && $found['amount_usd'] && abs($r->amount_usd - $found['amount_usd']) <= 0.1 * max($r->amount_usd, $found['amount_usd'])));
            $merged = (bool) $round;
            if ($round) {
                $isNew = !DB::table('funding_round_sources')->where(['funding_round_id' => $round->id, 'news_article_id' => $article->id])->exists();
                $round->fill(array_filter([
                    'round_type' => $round->round_type === 'undisclosed' && $found['round_type'] !== 'undisclosed' ? $found['round_type'] : null,
                    'amount' => $round->amount === null ? $found['amount'] : null, 'currency' => $round->amount === null ? $found['currency'] : null,
                    'amount_usd' => $round->amount === null ? $found['amount_usd'] : null,
                    'announced_on' => $on->lt($round->announced_on) ? $on : null,
                    'sources_count' => $isNew ? $round->sources_count + 1 : null,
                ], fn ($v) => $v !== null))->save();
            } else {
                $round = FundingRound::create(['company_id' => $this->companyId($article, $key), 'company_name' => $found['company'], 'company_key' => $key,
                    'round_type' => $found['round_type'], 'amount' => $found['amount'], 'currency' => $found['currency'], 'amount_usd' => $found['amount_usd'],
                    'announced_on' => $on, 'headline' => Str::limit($found['headline'], 490, ''), 'source_url' => $article->source_url]);
            }
            DB::table('funding_round_sources')->insertOrIgnore(['funding_round_id' => $round->id, 'news_article_id' => $article->id]);
            foreach ([true => $found['leads'], false => $found['investors']] as $lead => $names) {
                foreach ($names as $name) {
                    if (!$investor = $this->investor($name)) continue;
                    $existing = DB::table('funding_round_investor')->where(['funding_round_id' => $round->id, 'investor_id' => $investor->id])->first();
                    if (!$existing) DB::table('funding_round_investor')->insert(['funding_round_id' => $round->id, 'investor_id' => $investor->id, 'is_lead' => (bool) $lead]);
                    elseif ($lead && !$existing->is_lead) DB::table('funding_round_investor')->where(['funding_round_id' => $round->id, 'investor_id' => $investor->id])->update(['is_lead' => true]);
                }
            }
            return [$round, $merged];
        });
    }

    // Prefer the company the news pipeline already tagged; fall back to the name, but not for very short names ("Jar") that match too much.
    private function companyId(NewsArticle $article, string $key): ?int
    {
        $tagged = DB::table('news_article_companies')->join('companies', 'companies.id', '=', 'news_article_companies.company_id')
            ->where('news_article_id', $article->id)->where('companies.name_key', $key)->value('companies.id');
        if ($tagged || mb_strlen($key) < 4) return $tagged;
        return Company::where('name_key', $key)->orderBy('id')->value('id');
    }

    private function investor(string $name): ?Investor
    {
        $key = Company::nameKey($name);
        if (mb_strlen($key) < 2) return null;
        if ($investor = Investor::where('name_key', $key)->first()) return $investor;
        $type = $this->extractor->investorType($name);
        $slug = Str::slug($name) ?: $key;
        if (Investor::where('slug', $slug)->exists()) $slug .= '-' . substr(md5($key), 0, 6);
        return Investor::create(['name' => $name, 'name_key' => $key, 'slug' => $slug, 'type' => $type,
            'company_id' => $type === 'corporate' && mb_strlen($key) >= 4 ? Company::where('name_key', $key)->orderBy('id')->value('id') : null]);
    }

    private function recount(array $ids): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            $rows = DB::table('funding_round_investor')->join('funding_rounds', 'funding_rounds.id', '=', 'funding_round_investor.funding_round_id')
                ->whereIn('investor_id', $chunk)->groupBy('investor_id')
                ->selectRaw('investor_id, count(*) as n, sum(is_lead) as leads, max(announced_on) as last_on')->get();
            foreach ($rows as $r) Investor::whereKey($r->investor_id)->update(['rounds_count' => $r->n, 'lead_count' => (int) $r->leads, 'last_invested_on' => $r->last_on]);
        }
    }
}
