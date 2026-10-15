<?php

namespace App\Services\Trading;

use App\Models\Trading\MfScheme;
use Illuminate\Database\Eloquent\Builder;

/**
 * One filter language for the fund explorer, collections and the API:
 * q, amc[], asset_class[], sub_category[], category[] (ids), risk[], min_rating, plan, option, is_index, sort, dir.
 */
class FundQuery
{
    public const SORTS = ['return_1y', 'return_3y', 'return_5y', 'return_6m', 'nav', 'name', 'rating'];

    public function build(array $f): Builder
    {
        $list = fn ($key) => array_values(array_filter((array) ($f[$key] ?? []), fn ($v) => $v !== null && $v !== ''));
        $q = trim((string) ($f['q'] ?? ''));
        $plan = $f['plan'] ?? 'Direct';
        $option = $f['option'] ?? 'Growth';
        $query = MfScheme::query()->where('is_active', true)->where('scheme_type', 'Open Ended')
            ->when($q !== '', function ($query) use ($q) {
                $words = collect(preg_split('/[^\pL\pN]+/u', mb_strtolower($q)))->filter(fn ($w) => mb_strlen($w) >= 2);
                if ($words->isEmpty()) return;
                $query->where(fn ($w) => $w->whereFullText('name', $words->map(fn ($x) => '+' . $x . '*')->implode(' '), ['mode' => 'boolean'])->orWhere('amc', 'like', '%' . $q . '%'));
            })
            ->when($list('amc'), fn ($query, $v) => $query->where(fn ($w) => $w->whereIn('amc', $v)->orWhereIn('amc_id', array_filter($v, 'is_numeric'))))
            ->when($list('asset_class'), fn ($query, $v) => $query->whereIn('asset_class', $v))
            ->when($list('sub_category'), fn ($query, $v) => $query->whereIn('sub_category', $v))
            ->when($list('category'), fn ($query, $v) => $query->whereIn('category_id', $v))
            ->when($list('risk'), fn ($query, $v) => $query->whereIn('risk_level', $v))
            ->when((int) ($f['min_rating'] ?? 0), fn ($query, $v) => $query->where('rating', '>=', $v))
            ->when(!empty($f['is_index']), fn ($query) => $query->where('is_index', true))
            ->when($plan !== 'all', fn ($query) => $query->where('plan', $plan))
            ->when($option !== 'all', fn ($query) => $query->where('option', $option));
        $sort = in_array($f['sort'] ?? '', self::SORTS, true) ? $f['sort'] : 'return_3y';
        $dir = ($f['dir'] ?? ($sort === 'name' ? 'asc' : 'desc')) === 'asc' ? 'asc' : 'desc';
        return $sort === 'name' ? $query->orderBy('name', $dir) : $query->orderByRaw($sort . ' IS NULL')->orderBy($sort, $dir)->orderBy('name');
    }
}
