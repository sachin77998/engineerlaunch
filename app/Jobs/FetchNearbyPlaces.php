<?php

namespace App\Jobs;

use App\Models\Property\NearbyPlace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

// "Places nearby" for a map pin: one Overpass (OpenStreetMap) query covering every category in popt_nearby_categories,
// keeping the closest few of each within the radius. Stored per ~100 m spot so listings in the same building share it.
class FetchNearbyPlaces implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;
    public int $backoff = 120;
    public const RADIUS = 3000;
    public const PER_CATEGORY = 3;

    public function __construct(public float $lat, public float $lng) {}

    public function handle(): void
    {
        $categories = collect(config('properties.nearby_categories'))->filter(fn ($c) => !empty($c[2]) && str_contains($c[2], '='));
        if ($categories->isEmpty()) return;
        $parts = $categories->map(function ($c) {
            [$k, $v] = explode('=', $c[2], 2);
            return sprintf('nwr["%s"="%s"]["name"](around:%d,%F,%F);', $k, $v, self::RADIUS, $this->lat, $this->lng);
        })->implode('');
        $response = Http::withHeaders(['User-Agent' => config('discovery.user_agent', 'naukri-com')])->asForm()->timeout(90)
            ->post(config('discovery.overpass_url'), ['data' => "[out:json][timeout:60];($parts);out center 400;"]);
        if (!$response->successful()) throw new RuntimeException('Overpass returned HTTP ' . $response->status());

        $rows = [];
        foreach ($response->json('elements') ?? [] as $el) {
            $lat = $el['lat'] ?? $el['center']['lat'] ?? null;
            $lng = $el['lon'] ?? $el['center']['lon'] ?? null;
            $name = $el['tags']['name:en'] ?? $el['tags']['name'] ?? null;
            if (!$lat || !$name) continue;
            $category = $categories->search(function ($c) use ($el) {
                [$k, $v] = explode('=', $c[2], 2);
                return ($el['tags'][$k] ?? null) === $v;
            });
            if ($category === false) continue;
            $rows[] = ['category' => $category, 'name' => mb_substr($name, 0, 191), 'latitude' => $lat, 'longitude' => $lng,
                'distance_m' => (int) round(self::metres($this->lat, $this->lng, $lat, $lng)), 'source_id' => $el['type'][0] . $el['id']];
        }
        $key = NearbyPlace::geoKey($this->lat, $this->lng);
        $keep = collect($rows)->sortBy('distance_m')->unique(fn ($r) => $r['category'] . '|' . mb_strtolower($r['name']))
            ->groupBy('category')->flatMap(fn ($g) => $g->take(self::PER_CATEGORY))
            ->map(fn ($r) => $r + ['geo_key' => $key, 'fetched_at' => now()])->values()->all();
        DB::transaction(function () use ($key, $keep) {
            NearbyPlace::where('geo_key', $key)->delete();
            // An empty answer still records the fetch so the spot is not queried again for 90 days.
            NearbyPlace::insert($keep ?: [['geo_key' => $key, 'category' => '_none', 'name' => '', 'latitude' => $this->lat, 'longitude' => $this->lng, 'distance_m' => 0, 'source_id' => null, 'fetched_at' => now()]]);
        });
    }

    public static function metres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000; $dLat = deg2rad($lat2 - $lat1); $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($a)));
    }
}
