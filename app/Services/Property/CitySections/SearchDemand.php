<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;
use Illuminate\Support\Facades\DB;

// "Demand in <city>": the most searched localities in the last 90 days, overall and per home type, as a share of searches.
class SearchDemand extends CitySection
{
    public int $minutes = 60;

    public function data(CityContext $c, array $options): ?array
    {
        $rows = DB::table('property_search_logs')->where('city_slug', $c->slug())->whereNotNull('locality')->where('created_at', '>', now()->subDays(90))
            ->when($c->zoneLocalities !== null, fn ($q) => $q->whereIn('locality', $c->zoneLocalities ?: ['']))
            ->groupBy('locality', 'home_type')->selectRaw('locality, home_type, count(*) as n')->get();
        if ($rows->sum('n') < ($options['min_searches'] ?? 5)) return null;
        $zones = DB::table('property_localities')->join('property_cities', 'property_cities.id', '=', 'property_localities.city_id')
            ->where('property_cities.slug', $c->slug())->whereNotNull('zone')->pluck('zone', 'property_localities.name')->mapWithKeys(fn ($z, $n) => [mb_strtolower($n) => $z]);
        $top = function ($set) use ($zones, $c) {
            $total = $set->sum('n');
            return $set->groupBy(fn ($r) => mb_strtolower($r->locality))->map(fn ($g) => ['name' => $g->first()->locality, 'n' => $g->sum('n')])
                ->sortByDesc('n')->take(5)->map(fn ($l) => $l + ['share' => round($l['n'] / $total * 100), 'zone' => $zones[mb_strtolower($l['name'])] ?? null,
                    'url' => $c->search('buy', ['q' => [$l['name']]])])->values();
        };
        $types = config('properties.home_types');
        $tabs = ['All' => $top($rows)];
        foreach ($rows->whereNotNull('home_type')->groupBy('home_type') as $type => $set) {
            if (isset($types[$type]) && $set->sum('n') >= 3) $tabs[$types[$type][1] ?? $types[$type][0]] = $top($set);
        }
        return ['tabs' => $tabs, 'total' => $rows->sum('n')];
    }
}
