<?php

namespace App\Services\Trading;

use App\Models\Trading\MfScheme;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Recommendations after viewing or buying a fund: other funds of the same fund house (one per category, best
 * 3-year return), the best funds of the same category from other houses, and funds other investors bought too.
 * Matches the plan (Direct/Regular) and option (Growth/IDCW) of the fund.
 */
class FundRecommender
{
    /** @return array{same_amc: Collection, same_category: Collection, also_bought: Collection} */
    public function for(MfScheme $scheme, int $limit = 6, array $excludeCodes = []): array
    {
        $key = 'trading:recommend:' . $scheme->scheme_code . ':' . md5(implode(',', $excludeCodes));
        return Cache::remember($key, 3600, function () use ($scheme, $limit, $excludeCodes) {
            $exclude = array_merge([$scheme->scheme_code], $excludeCodes);
            $base = fn () => MfScheme::where('is_active', true)->where('scheme_type', 'Open Ended')->where('asset_class', '!=', 'ETF')
                ->whereNotIn('scheme_code', $exclude)
                ->when($scheme->plan, fn ($q) => $q->where('plan', $scheme->plan))
                ->when($scheme->option, fn ($q) => $q->where('option', $scheme->option));

            $sameAmc = $base()->where('amc', $scheme->amc)->where('category', '!=', $scheme->category)
                ->orderByRaw('return_3y IS NULL')->orderByDesc('return_3y')->limit(60)->get()
                ->unique('category')->take($limit)->values();
            $sameCategory = $base()->where('category', $scheme->category)->where('amc', '!=', $scheme->amc)
                ->orderByRaw('return_3y IS NULL')->orderByDesc('return_3y')->limit($limit)->get();
            $alsoCodes = DB::table('mf_orders as a')->join('mf_orders as b', 'a.trading_client_id', '=', 'b.trading_client_id')
                ->where('a.scheme_code', $scheme->scheme_code)->where('b.scheme_code', '!=', $scheme->scheme_code)
                ->where('a.side', 'buy')->where('b.side', 'buy')->whereIn('b.status', ['submitted', 'allotted'])
                ->selectRaw('b.scheme_code, count(distinct b.trading_client_id) as buyers')->groupBy('b.scheme_code')
                ->orderByDesc('buyers')->limit($limit)->pluck('scheme_code')->all();
            $alsoBought = $alsoCodes ? MfScheme::whereIn('scheme_code', array_diff($alsoCodes, $exclude))->get() : collect();
            return ['same_amc' => $sameAmc, 'same_category' => $sameCategory, 'also_bought' => $alsoBought];
        });
    }
}
