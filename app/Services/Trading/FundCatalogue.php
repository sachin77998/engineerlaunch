<?php

namespace App\Services\Trading;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds the browsable fund catalogue from mf_schemes: asset class / sub-category / SEBI risk level per scheme,
 * fund houses, categories with counts, star ratings within each category, and the collections
 * (High return, Best SIP funds, Gold & Silver, Large/Mid/Small Cap…) stored in mf_collections.
 */
class FundCatalogue
{
    public const ALIASES = [
        'Sectoral' => 'Sectoral / Thematic', 'Thematic' => 'Sectoral / Thematic',
        'Dynamic Asset Allocation or Balanced Advantage' => 'Balanced Advantage', 'Balanced Advantage Fund/ Dynamic Asset Allocation' => 'Balanced Advantage',
        'Short Term' => 'Short Duration', 'Ultra Short Term' => 'Ultra Short Duration', 'Medium Term' => 'Medium Duration',
        'Medium to Long Term' => 'Medium to Long Duration', 'Long Term' => 'Long Duration', 'Dynamic Term' => 'Dynamic Bond',
        'Banking and PSU Debt' => 'Banking and PSU', 'Floating Interest Rates' => 'Floater', 'Ultra Short to Short Term' => 'Low Duration',
    ];
    public const RISK_LEVELS = ['Low', 'Low to Moderate', 'Moderate', 'Moderately High', 'High', 'Very High'];

    /** Collections shown on the explore page; rules are filters understood by FundQuery. */
    public const COLLECTIONS = [
        'high-return' => ['High return', 'Equity funds with the best 3-year returns', 'rocket', ['asset_class' => ['Equity'], 'min_rating' => 3, 'sort' => 'return_3y']],
        'best-sip-funds' => ['Best SIP funds', 'Consistent diversified equity funds for monthly investing', 'calendar', ['sub_category' => ['Flexi Cap', 'Large Cap', 'Large & Mid Cap', 'Mid Cap', 'Multi Cap', 'ELSS', 'Focused', 'Value'], 'min_rating' => 4, 'sort' => 'return_5y']],
        'gold-silver' => ['Gold & Silver', 'Gold and silver funds and fund of funds', 'coins', ['asset_class' => ['Commodities'], 'sort' => 'return_1y']],
        'large-cap' => ['Large Cap', "India's 100 largest companies", 'building', ['sub_category' => ['Large Cap'], 'sort' => 'return_3y']],
        'mid-cap' => ['Mid Cap', 'Companies ranked 101–250 by size', 'buildings', ['sub_category' => ['Mid Cap'], 'sort' => 'return_3y']],
        'small-cap' => ['Small Cap', 'Companies ranked 251 and beyond', 'shop', ['sub_category' => ['Small Cap'], 'sort' => 'return_3y']],
        'flexi-cap' => ['Flexi Cap', 'Invest across company sizes', 'layers', ['sub_category' => ['Flexi Cap'], 'sort' => 'return_3y']],
        'tax-saver' => ['Tax saver (ELSS)', 'Save tax under Section 80C', 'receipt', ['sub_category' => ['ELSS'], 'sort' => 'return_3y']],
        'index-funds' => ['Index funds', 'Low-cost funds that track an index', 'line', ['is_index' => true, 'sort' => 'return_1y']],
        'sectoral' => ['Sectoral & thematic', 'Banking, technology, pharma, infrastructure…', 'grid', ['sub_category' => ['Sectoral', 'Thematic', 'Sectoral / Thematic'], 'sort' => 'return_1y']],
        'liquid' => ['Park your money', 'Liquid and overnight funds', 'drop', ['sub_category' => ['Liquid', 'Overnight', 'Money Market'], 'sort' => 'return_1y']],
        'hybrid' => ['Hybrid', 'A mix of equity and debt', 'balance', ['asset_class' => ['Hybrid'], 'sort' => 'return_3y']],
    ];

    /** [asset class, sub-category, risk level, is index fund] for a scheme. */
    public static function classify(?string $category, string $name): array
    {
        $c = (string) $category;
        $sub = trim(preg_replace(['/^.*?\s-\s/', '/\s*\bFunds?\b$/i', '/\bScheme\b/i'], '', $c)) ?: $c;
        $sub = trim(preg_replace('/\s+/', ' ', str_replace(['Sectoral/ Thematic', 'ELSS- Tax Saver', 'ELSS - Tax Saver'], ['Sectoral / Thematic', 'ELSS', 'ELSS'], $sub)));
        $sub = preg_replace(["/children[\u{2019}']?s[\u{2019}']?/iu", "/[\u{2019}\u{2018}]/u"], ["Children's", "'"], $sub);
        // AMFI still lists schemes under pre-2018 names; fold them into the current SEBI categories.
        $sub = self::ALIASES[$sub] ?? $sub;
        $isIndex = (bool) preg_match('/\bIndex Funds?\b/i', $c) || (bool) preg_match('/\b(index fund|nifty|sensex)\b/i', $name) && !preg_match('/\bETF\b/i', $name);
        $metal = preg_match('/\bgold\b/i', $name . ' ' . $c) ? 'Gold' : (preg_match('/\bsilver\b/i', $name . ' ' . $c) ? 'Silver' : null);
        if ($metal && !preg_match('/equity|multi asset|hybrid/i', $c)) return ['Commodities', $metal, 'High', false];

        $asset = MarketDataSync::assetClass($c) ?? 'Other';
        if ($asset === 'Index Fund') $sub = preg_match('/debt|gilt|bond|sdl|g-sec|crisil|psu/i', $name . ' ' . $c) ? 'Debt Index' : 'Equity Index';
        if ($asset === 'Fund of Funds') $sub = preg_match('/overseas|international|global|us\b|nasdaq|world/i', $name . ' ' . $c) ? 'International FoF' : 'Domestic FoF';
        if ($asset === 'ETF') $sub = preg_match('/debt|gilt|bond|liquid/i', $name . ' ' . $c) ? 'Debt ETF' : 'Equity ETF';
        $risk = match (true) {
            (bool) preg_match('/overnight/i', $c) => 'Low',
            (bool) preg_match('/liquid|money market|arbitrage/i', $c) => 'Low to Moderate',
            (bool) preg_match('/ultra short|low duration|floater|banking and psu|gilt|short duration|corporate bond|target maturity/i', $c) || in_array($sub, ['Debt Index', 'Debt ETF'], true) => 'Moderate',
            (bool) preg_match('/medium|long duration|dynamic bond|credit risk|conservative|equity savings/i', $c) => 'Moderately High',
            (bool) preg_match('/balanced advantage|dynamic asset|multi asset/i', $c) => 'High',
            in_array($asset, ['Debt'], true) => 'Moderate',
            default => 'Very High',
        };
        return [$asset, Str::limit($sub, 78, ''), $risk, $isIndex];
    }

    /** Re-derives classification, fund houses, categories, ratings and collections. */
    public function rebuild(): array
    {
        // 1. Classification per scheme.
        $updates = [];
        foreach (DB::table('mf_schemes')->get(['id', 'category', 'name']) as $s) {
            [$asset, $sub, $risk, $index] = self::classify($s->category, $s->name);
            $updates[] = ['id' => $s->id, 'asset_class' => $asset, 'sub_category' => $sub, 'risk_level' => $risk, 'is_index' => $index];
        }
        foreach (array_chunk($updates, 500) as $chunk) {
            DB::transaction(function () use ($chunk) {
                foreach ($chunk as $u) DB::table('mf_schemes')->where('id', $u['id'])->update(array_diff_key($u, ['id' => 1]));
            });
        }

        // 2. Fund houses.
        $now = now();
        $amcs = DB::table('mf_schemes')->where('is_active', true)->selectRaw('amc, count(*) n, max(return_3y) best')->groupBy('amc')->get();
        foreach ($amcs as $a) {
            if (!$a->amc) continue;
            $short = trim(preg_replace('/\s*Mutual Fund$/i', '', $a->amc));
            DB::table('mf_amcs')->updateOrInsert(['name' => $a->amc], ['slug' => Str::slug($short), 'short_name' => $short, 'color' => self::color($short),
                'scheme_count' => $a->n, 'best_return_3y' => $a->best, 'updated_at' => $now, 'created_at' => $now]);
        }
        DB::statement('UPDATE mf_schemes s JOIN mf_amcs a ON a.name = s.amc SET s.amc_id = a.id');

        // 3. Categories.
        $order = ['Equity' => 1, 'Debt' => 2, 'Hybrid' => 3, 'Commodities' => 4, 'Index Fund' => 5, 'ETF' => 6, 'Fund of Funds' => 7, 'Solution Oriented' => 8, 'Other' => 9];
        $cats = DB::table('mf_schemes')->where('is_active', true)->selectRaw('asset_class, sub_category, min(risk_level) risk, count(*) n, avg(return_3y) avg3')->groupBy('asset_class', 'sub_category')->get();
        foreach ($cats as $c) {
            if (!$c->asset_class || !$c->sub_category) continue;
            DB::table('mf_categories')->updateOrInsert(['slug' => Str::slug($c->asset_class . ' ' . $c->sub_category)], [
                'asset_class' => $c->asset_class, 'sub_category' => $c->sub_category, 'risk_level' => $c->risk, 'scheme_count' => $c->n,
                'avg_return_3y' => $c->avg3 !== null ? round($c->avg3, 2) : null, 'sort_order' => $order[$c->asset_class] ?? 9, 'updated_at' => $now, 'created_at' => $now]);
        }
        $current = $cats->filter(fn ($c) => $c->asset_class && $c->sub_category)->map(fn ($c) => Str::slug($c->asset_class . ' ' . $c->sub_category))->all();
        DB::table('mf_schemes')->whereNotNull('category_id')->update(['category_id' => null]);
        DB::table('mf_categories')->whereNotIn('slug', $current ?: ['-'])->delete();
        DB::statement('UPDATE mf_schemes s JOIN mf_categories c ON c.asset_class = s.asset_class AND c.sub_category = s.sub_category SET s.category_id = c.id');

        // 4. Ratings: rank by 3-year return (5-year when available) within the sub-category, Direct Growth peers only.
        DB::table('mf_schemes')->update(['rating' => null]);
        $rated = 0;
        DB::table('mf_schemes')->where('is_active', true)->whereNotNull('return_3y')->whereNotNull('sub_category')
            ->get(['id', 'sub_category', 'return_3y', 'return_5y'])->groupBy('sub_category')
            ->each(function ($group) use (&$rated) {
                if ($group->count() < 5) return;
                $sorted = $group->sortByDesc(fn ($s) => $s->return_3y * 0.6 + ($s->return_5y ?? $s->return_3y) * 0.4)->values();
                $n = $sorted->count();
                foreach ($sorted as $i => $s) {
                    $pct = $i / $n;   // 0 = best
                    $stars = $pct < 0.10 ? 5 : ($pct < 0.325 ? 4 : ($pct < 0.675 ? 3 : ($pct < 0.90 ? 2 : 1)));
                    DB::table('mf_schemes')->where('id', $s->id)->update(['rating' => $stars]);
                    $rated++;
                }
            });

        // 5. Collections.
        $sort = 0;
        foreach (self::COLLECTIONS as $slug => [$title, $description, $icon, $rules]) {
            $count = app(FundQuery::class)->build(array_merge($rules, ['plan' => 'Direct', 'option' => 'Growth']))->count();
            DB::table('mf_collections')->updateOrInsert(['slug' => $slug], ['title' => $title, 'description' => $description, 'icon' => $icon,
                'rules' => json_encode($rules), 'sort_order' => ++$sort, 'is_active' => true, 'fund_count' => $count, 'updated_at' => $now, 'created_at' => $now]);
        }
        foreach (['trading:fund-facets', 'trading:catalogue'] as $key) \App\Support\Stale::forget($key);
        return ['schemes' => count($updates), 'amcs' => $amcs->count(), 'categories' => $cats->count(), 'rated' => $rated];
    }

    /** A stable brand-like colour per fund house. */
    public static function color(string $name): string
    {
        $palette = ['#d62828', '#1d4ed8', '#0f766e', '#7c3aed', '#c2410c', '#0369a1', '#15803d', '#b45309', '#be185d', '#4338ca', '#0e7490', '#a16207'];
        return $palette[abs(crc32(strtolower($name))) % count($palette)];
    }

    /** Two-letter mark for a fund house (HDFC → HD, Nippon India → NI). */
    public static function initials(string $name): string
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim(preg_replace('/\b(Mutual Fund|Asset Management|AMC|Ltd|Limited)\b/i', '', $name)))));
        if (!$words) return '?';
        if (count($words) === 1) return mb_strtoupper(mb_substr($words[0], 0, 2));
        return mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
    }
}
