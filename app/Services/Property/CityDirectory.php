<?php

namespace App\Services\Property;

use App\Models\Property\Property;
use App\Models\Property\PropertyCity;
use Illuminate\Support\Facades\DB;

// Cities for the "Choose a city" chooser and the visitor's "Pick up where you left off" chips.
class CityDirectory
{
    public const TRAIL_KEY = 'property_city_trail';

    /** Active cities with zones, busiest first: [{name, slug, country, state, zones, count}]. */
    public function all(): array
    {
        return PropertyCache::remember('_all', 'city-directory', now()->addMinutes(30), function () {
            $counts = Property::live()->whereNotNull('city_slug')->groupBy('city_slug')->selectRaw('city_slug, count(*) as n')->pluck('n', 'city_slug');
            return PropertyCity::where('is_active', true)->get(['name', 'slug', 'country', 'state', 'zones', 'sort'])
                ->map(fn ($c) => ['name' => $c->name, 'slug' => $c->slug, 'country' => $c->country, 'state' => $c->state, 'zones' => array_values((array) $c->zones), 'count' => (int) ($counts[$c->slug] ?? 0)])
                ->sortBy(fn ($c) => [-$c['count'], $c['country'] === 'India' ? 0 : 1, $c['name']])->values()->all();
        });
    }

    /** Remembers a city page visit for the chips (session only; searches are logged separately). */
    public function remember(string $slug, string $name, ?string $zone): void
    {
        $trail = collect(session(self::TRAIL_KEY, []))->reject(fn ($t) => $t['slug'] === $slug && $t['zone'] === $zone)->prepend(['slug' => $slug, 'name' => $name, 'zone' => $zone])->take(6);
        session([self::TRAIL_KEY => $trail->values()->all()]);
    }

    /** Up to $limit chips like "Buy in Bengaluru East" / "Rent in Pune", newest first. */
    public function trail(array $visitorIds, int $limit = 4): array
    {
        $chips = collect(session(self::TRAIL_KEY, []))->map(fn ($t) => ['label' => 'Buy in ' . ($t['zone'] ?: $t['name']), 'icon' => '🏢',
            'url' => route('real-estate.city', array_filter([$t['slug'], 'zone' => $t['zone']]))]);
        $modes = config('properties.modes');
        $names = collect($this->all())->pluck('name', 'slug');
        $searched = DB::table('property_search_logs')->whereIn('visitor', $visitorIds)->whereNotNull('city_slug')->latest('created_at')->limit(40)->get(['mode', 'city_slug'])
            ->unique(fn ($r) => $r->mode . '|' . $r->city_slug)
            ->map(fn ($r) => ['label' => ($modes[$r->mode]['label'] ?? ucfirst($r->mode)) . ' in ' . ($names[$r->city_slug] ?? ucwords(str_replace('-', ' ', $r->city_slug))),
                'icon' => str_contains($r->mode, 'rent') ? '🔑' : '🏢',
                'url' => $r->mode === 'buy' ? route('real-estate.city', $r->city_slug) : route('properties.search', [$r->mode, $r->city_slug])]);
        return $chips->merge($searched)->unique('label')->take($limit)->values()->all();
    }

    /** Nearest city with coordinates (within $km) to a point. */
    public function nearest(float $lat, float $lng, float $km = 75): ?array
    {
        $best = null;
        foreach (PropertyCity::where('is_active', true)->whereNotNull('latitude')->get(['name', 'slug', 'latitude', 'longitude']) as $c) {
            $d = \App\Jobs\FetchNearbyPlaces::metres($lat, $lng, (float) $c->latitude, (float) $c->longitude) / 1000;
            if ($d <= $km && (!$best || $d < $best['km'])) $best = ['name' => $c->name, 'slug' => $c->slug, 'km' => round($d, 1)];
        }
        return $best;
    }
}
