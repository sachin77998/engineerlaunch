<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;
use Illuminate\Support\Facades\DB;

// Listings like the ones the visitor viewed here (same rent / sale, home type and a similar price), else the newest in the city.
class Recommended extends CitySection
{
    public int $minutes = 0;

    public function data(CityContext $c, array $options): ?array
    {
        $views = DB::table('property_view_logs')->whereIn('visitor', $c->visitorIds)->where('city_slug', $c->slug())->latest('created_at')->limit(20)->get();
        $q = $c->listings()->with(['media', 'rentTerms', 'saleTerms', 'pgTerms', 'owner:id,owner_type,is_featured'])->whereNotIn('id', $views->pluck('property_id'));
        if ($views->isNotEmpty()) {
            $listing = $views->countBy('listing')->sortDesc()->keys()->first();
            $types = $views->pluck('home_type')->filter()->unique()->all();
            $price = $views->where('listing', $listing)->pluck('price')->filter()->median();
            $q->where('listing', $listing)
                ->when($types, fn ($x) => $x->orderByRaw('coalesce(building_type, kind) in (' . implode(',', array_fill(0, count($types), '?')) . ') desc', $types))
                ->when($price, fn ($x) => $x->orderByRaw('abs(price_sort - ?) / ? asc', [$price, $price]));
        }
        $items = $q->latest('published_at')->limit(8)->get();
        return $items->isEmpty() ? null : ['items' => $items, 'personal' => $views->isNotEmpty()];
    }
}
