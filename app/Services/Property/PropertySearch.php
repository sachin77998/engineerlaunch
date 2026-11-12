<?php

namespace App\Services\Property;

use App\Models\Property\Property;
use Illuminate\Database\Eloquent\Builder;

// The listing search shared by the search page, saved searches and alerts: validation rules, the filtered query and a readable label.
class PropertySearch
{
    public const SORTS = ['newest' => 'Newest first', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'area_desc' => 'Largest first'];

    /** Query-string rules for /properties/{mode}/{city?}. */
    public static function rules(): array
    {
        $o = config('properties');
        return [
            'q' => 'nullable|array|max:3', 'q.*' => 'nullable|string|max:100',
            'kind' => 'nullable|string|max:24',
            'type' => 'nullable|array', 'type.*' => 'in:' . implode(',', array_keys($o['home_types'])),
            'bhk' => 'nullable|array', 'bhk.*' => 'in:' . implode(',', array_unique(array_merge(array_keys($o['bhk']), array_keys($o['bhk_groups'])))),
            'possession' => 'nullable|array', 'possession.*' => 'in:new_launch,under_construction,ready', 'new_projects' => 'nullable|boolean',
            'posted_by' => 'nullable|array', 'posted_by.*' => 'in:' . implode(',', array_keys($o['posted_by'])),
            'furnishing' => 'nullable|array', 'furnishing.*' => 'in:' . implode(',', array_keys($o['furnishing'])),
            'tenant' => 'nullable|in:' . implode(',', array_keys($o['preferred_tenants'])), 'gender' => 'nullable|in:male,female',
            'min' => 'nullable|numeric|min:0', 'max' => 'nullable|numeric|min:0',
            'area_min' => 'nullable|numeric|min:0', 'area_max' => 'nullable|numeric|min:0', 'area_unit' => 'nullable|in:' . implode(',', array_keys($o['area_units'])),
            'loc' => 'nullable|array|max:30', 'loc.*' => 'string|max:150', 'project' => 'nullable|array|max:30', 'project.*' => 'string|max:160',
            'amen' => 'nullable|array', 'amen.*' => 'in:' . implode(',', $o['amenity_filters']),
            'verified' => 'nullable|boolean', 'photos' => 'nullable|boolean', 'videos' => 'nullable|boolean', 'rera' => 'nullable|boolean',
            'sort' => 'nullable|in:' . implode(',', array_keys(self::SORTS)),
        ];
    }

    /** Live listings of a mode (buy, rent, commercial-rent, commercial-buy). */
    public static function mode(string $mode): Builder
    {
        $m = config("properties.modes.$mode");
        return Property::live()->where('listing', $m['listing'])->where('segment', $m['segment'])->when($m['kinds'] ?? null, fn ($q, $kinds) => $q->whereIn('kind', $kinds));
    }

    /** Listings matching the filters, unsorted. */
    public static function query(string $mode, ?string $city, array $f): Builder
    {
        $o = config('properties');
        $areas = self::areas($f);
        $bhk = collect($f['bhk'] ?? [])->flatMap(fn ($b) => $o['bhk_groups'][$b] ?? [$b])->unique()->values()->all();
        $unit = $f['area_unit'] ?? 'sqft';
        $sqft = fn ($v) => PropertyFormat::toSqft((float) $v, $unit);

        return self::mode($mode)->when($city, fn ($q) => $q->where('city_slug', $city))
            ->when($areas->isNotEmpty(), fn ($q) => $q->where(fn ($w) => $areas->each(fn ($a) => $w->orWhere('locality', 'like', "%$a%")->orWhere('society', 'like', "%$a%")
                ->orWhere('landmark', 'like', "%$a%")->orWhere('street', 'like', "%$a%"))))
            ->when($f['kind'] ?? null, fn ($q, $v) => $q->where('kind', $v))
            // Type of property: a plot is the land kind; the rest are building types of a house or flat.
            ->when($f['type'] ?? null, fn ($q, $types) => $q->where(fn ($w) => collect($types)->each(fn ($t) => $o['home_types'][$t][2] === null
                ? $w->orWhere('kind', 'land')
                : $w->orWhere(fn ($x) => $x->where('kind', '!=', 'land')->whereIn('building_type', $o['home_types'][$t][2])))))
            ->when($bhk, fn ($q, $v) => $q->whereIn('bhk', $v))
            ->when($f['furnishing'] ?? null, fn ($q, $v) => $q->whereIn('furnishing', $v))
            ->when($f['possession'] ?? null, fn ($q, $v) => $q->whereHas('saleTerms', fn ($s) => $s->where(function ($w) use ($v) {
                $stages = array_values(array_diff($v, ['new_launch']));
                if ($stages) $w->whereIn('possession', $stages);
                if (in_array('new_launch', $v, true)) $w->orWhere('is_new_project', true);
            })))
            ->when($f['new_projects'] ?? null, fn ($q) => $q->whereHas('saleTerms', fn ($s) => $s->where('is_new_project', true)))
            ->when($f['posted_by'] ?? null, fn ($q, $v) => $q->whereHas('owner', fn ($w) => $w->where(function ($x) use ($v) {
                foreach ($v as $by) match ($by) {
                    'owner' => $x->orWhere('owner_type', 'owner'), 'builder' => $x->orWhere('owner_type', 'builder'),
                    'dealer' => $x->orWhere('owner_type', 'agent'), 'featured' => $x->orWhere(fn ($y) => $y->where('owner_type', 'agent')->where('is_featured', true)),
                };
            })))
            ->when($f['tenant'] ?? null, fn ($q, $v) => $q->whereHas('rentTerms', fn ($r) => $r->where(fn ($w) => $w->whereJsonContains('preferred_tenants', $v)->orWhereJsonContains('preferred_tenants', 'any')->orWhereNull('preferred_tenants'))))
            ->when($f['gender'] ?? null, fn ($q, $v) => $q->whereHas('pgTerms', fn ($p) => $p->whereIn('gender', [$v, 'any'])))
            ->when(isset($f['min']), fn ($q) => $q->where('price_sort', '>=', $f['min']))
            ->when(isset($f['max']), fn ($q) => $q->where('price_sort', '<=', $f['max']))
            ->when(isset($f['area_min']), fn ($q) => $q->where('area_sqft', '>=', $sqft($f['area_min'])))
            ->when(isset($f['area_max']), fn ($q) => $q->where('area_sqft', '<=', $sqft($f['area_max'])))
            ->when($f['verified'] ?? null, fn ($q) => $q->where('is_verified', true))
            ->when($f['photos'] ?? null, fn ($q) => $q->whereHas('media', fn ($m) => $m->where('type', 'photo')))
            ->when($f['videos'] ?? null, fn ($q) => $q->whereHas('media', fn ($m) => $m->where('type', 'video')))
            // RERA approved: posted by someone who gave a RERA registration number.
            ->when($f['rera'] ?? null, fn ($q) => $q->whereHas('owner', fn ($w) => $w->whereNotNull('rera_number')->where('rera_number', '!=', '')))
            ->when($f['loc'] ?? null, fn ($q, $v) => $q->whereIn('locality', $v))
            ->when($f['project'] ?? null, fn ($q, $v) => $q->where(fn ($w) => $w->whereIn('society', $v)->orWhereHas('saleTerms', fn ($s) => $s->whereIn('project_name', $v))))
            // Every ticked amenity must be present; "gated" also counts the gated-security detail and highlight, "parking" any parking count.
            ->when($f['amen'] ?? null, function ($q, $v) {
                foreach ($v as $a) $q->where(fn ($w) => $w->whereJsonContains('amenities', $a)
                    ->when($a === 'gated', fn ($x) => $x->orWhereJsonContains('highlights', 'gated')->orWhere('details->gated', 'yes'))
                    ->when($a === 'parking', fn ($x) => $x->orWhere('details->covered_parking', '>', 0)->orWhere('details->open_parking', '>', 0)));
                return $q;
            });
    }

    public static function sort(Builder $query, ?string $sort): Builder
    {
        match ($sort ?? 'newest') {
            'price_asc' => $query->orderByRaw('price_sort is null')->orderBy('price_sort'),
            'price_desc' => $query->orderByDesc('price_sort'),
            'area_desc' => $query->orderByDesc('area_sqft'),
            default => $query->latest('published_at'),
        };
        return $query;
    }

    public static function areas(array $f)
    {
        return collect($f['q'] ?? [])->map(fn ($v) => trim((string) $v))->filter()->take(3)->values();
    }

    /** "2 BHK · Buy in Whitefield, Bengaluru". */
    public static function label(string $mode, array $f, ?string $cityName): string
    {
        $areas = self::areas($f);
        return trim(collect([implode(', ', array_map(fn ($b) => config("properties.bhk.$b") ?? $b, $f['bhk'] ?? [])), config("properties.modes.$mode.label")])->filter()->implode(' · ')
            . ($areas->isNotEmpty() ? ' in ' . $areas->implode(', ') : '') . ($cityName ? ($areas->isNotEmpty() ? ', ' : ' in ') . $cityName : ''));
    }

    /** Filters with empty values dropped and sort removed — what a saved search stores. */
    public static function clean(array $f): array
    {
        unset($f['sort'], $f['page']);
        return array_filter($f, fn ($v) => $v !== null && $v !== '' && $v !== [] && $v !== false && $v !== '0');
    }
}
