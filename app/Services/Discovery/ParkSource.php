<?php

namespace App\Services\Discovery;

use App\Models\IndustrialArea;
use App\Models\IndustrialState;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * IT parks, tech parks, SEZs and industrial areas/estates from OpenStreetMap (ODbL), one Indian state per request.
 * Each place gets the nearest mapped city or town as its city, and is saved to the industrial area directory so it
 * appears on /industrial-areas and is scanned for companies by the area discovery.
 */
class ParkSource
{
    // OpenStreetMap ISO 3166-2 codes; some states have an older and a newer code.
    public const STATES = [
        'Andhra Pradesh' => ['IN-AP'], 'Arunachal Pradesh' => ['IN-AR'], 'Assam' => ['IN-AS'], 'Bihar' => ['IN-BR'], 'Chhattisgarh' => ['IN-CT', 'IN-CG'],
        'Goa' => ['IN-GA'], 'Gujarat' => ['IN-GJ'], 'Haryana' => ['IN-HR'], 'Himachal Pradesh' => ['IN-HP'], 'Jharkhand' => ['IN-JH'], 'Karnataka' => ['IN-KA'],
        'Kerala' => ['IN-KL'], 'Madhya Pradesh' => ['IN-MP'], 'Maharashtra' => ['IN-MH'], 'Manipur' => ['IN-MN'], 'Meghalaya' => ['IN-ML'], 'Mizoram' => ['IN-MZ'],
        'Nagaland' => ['IN-NL'], 'Odisha' => ['IN-OR', 'IN-OD'], 'Punjab' => ['IN-PB'], 'Rajasthan' => ['IN-RJ'], 'Sikkim' => ['IN-SK'], 'Tamil Nadu' => ['IN-TN'],
        'Telangana' => ['IN-TG', 'IN-TS'], 'Tripura' => ['IN-TR'], 'Uttar Pradesh' => ['IN-UP'], 'Uttarakhand' => ['IN-UT', 'IN-UK'], 'West Bengal' => ['IN-WB'],
        'Delhi' => ['IN-DL'], 'Jammu and Kashmir' => ['IN-JK'], 'Ladakh' => ['IN-LA'], 'Puducherry' => ['IN-PY'], 'Chandigarh' => ['IN-CH'],
        'Andaman and Nicobar Islands' => ['IN-AN'], 'Dadra and Nagar Haveli and Daman and Diu' => ['IN-DH', 'IN-DN'], 'Lakshadweep' => ['IN-LD'],
    ];

    private const PARK = '(IT|I\\.T\\.|Tech|Techno|Info|Software|Cyber|Knowledge|Bio|Electronics?|Fintech)[ -]?(Park|City|Hub|Zone|polis|Valley|Tower)|SEZ|Special Economic Zone|Software Technology Park|STPI';
    private const INDUSTRIAL = 'Industrial (Area|Estate|Park|Zone|Growth Centre|Corridor|Township)|Focal Point|MIDC|GIDC|SIDCUL|SIIDCUL|RIICO|SIPCOT|KIADB|APIIC|TSIIC|HSIIDC|HSIDC|UPSIDA|UPSIDC|PSIEC|IMT |Udyog Vihar|Udyog Kendra|Auto Nagar|Autonagar';

    /** @return array<int, array{name:string,type:string,lat:float,lon:float,city:?string,osm:string,state:string}> */
    public function fetch(string $state): array
    {
        $codes = self::STATES[$state] ?? throw new RuntimeException("Unknown state {$state}");
        $area = implode('', array_map(fn ($code) => "area[\"ISO3166-2\"=\"{$code}\"];", $codes));
        $park = self::PARK;
        $industrial = self::INDUSTRIAL;
        $query = <<<OVERPASS
[out:json][timeout:150];
({$area})->.s;
(
  nwr["name"~"{$park}",i]["landuse"](area.s);
  nwr["name"~"{$park}",i]["office"](area.s);
  nwr["name"~"{$park}",i]["building"](area.s);
  nwr["name"~"{$industrial}",i]["landuse"](area.s);
  nwr["name"~"{$industrial}",i]["place"](area.s);
);
out tags center;
node["place"~"^(city|town)$"](area.s);
out tags;
OVERPASS;
        $response = null;
        foreach ((array) config('discovery.overpass_urls', [config('discovery.overpass_url', 'https://overpass-api.de/api/interpreter'), 'https://overpass.kumi.systems/api/interpreter']) as $url) {
            $response = Http::withHeaders(['User-Agent' => config('discovery.user_agent')])->asForm()->timeout(180)->post($url, ['data' => $query]);
            if ($response->successful()) break;
        }
        if (!$response || !$response->successful()) throw new RuntimeException('Overpass returned HTTP ' . ($response?->status() ?? 0));

        $places = [];
        $parks = [];
        foreach ($response->json('elements', []) as $element) {
            $tags = $element['tags'] ?? [];
            if (isset($tags['place']) && in_array($tags['place'], ['city', 'town'], true) && isset($element['lat'])) {
                $places[] = ['name' => $tags['name:en'] ?? $tags['name'] ?? null, 'lat' => $element['lat'], 'lon' => $element['lon'], 'city' => $tags['place'] === 'city'];
                continue;
            }
            $name = trim($tags['name:en'] ?? $tags['name'] ?? '');
            $lat = $element['center']['lat'] ?? $element['lat'] ?? null;
            $lon = $element['center']['lon'] ?? $element['lon'] ?? null;
            if ($name === '' || $lat === null || mb_strlen($name) > 140 || !preg_match('/\p{Latin}/u', $name)) continue;
            // Office buildings inside a park ("Red Hat India, IBC Knowledge Park") are companies, not parks.
            if (str_contains($name, ',') || preg_match('/\b(pvt|private|ltd|limited|inc|llp|depot|school|hospital|bank|atm|temple|police)\b/i', $name)) continue;
            $parks[mb_strtolower($name)] ??= [
                'name' => $name, 'type' => $this->type($name), 'lat' => (float) $lat, 'lon' => (float) $lon,
                'city' => $tags['addr:city'] ?? $tags['addr:district'] ?? null, 'osm' => ($element['type'] ?? 'way') . '/' . $element['id'], 'state' => $state,
            ];
        }
        $places = array_values(array_filter($places, fn ($p) => $p['name']));
        foreach ($parks as &$park) $park['city'] ??= $this->nearestPlace($places, $park['lat'], $park['lon']);
        return array_values($parks);
    }

    /** Saves new places; existing areas (matched by name and state) are only given coordinates if missing. */
    public function write(array $parks): array
    {
        $created = 0;
        $updated = 0;
        foreach ($parks as $park) {
            $stateId = IndustrialState::where('name', $park['state'])->value('id');
            if (!$stateId) continue;
            $existing = IndustrialArea::where('state_id', $stateId)->whereRaw('LOWER(name) = ?', [mb_strtolower($park['name'])])->first();
            if ($existing) {
                if (!$existing->latitude) { $existing->update(['latitude' => $park['lat'], 'longitude' => $park['lon']]); $updated++; }
                continue;
            }
            $slug = Str::limit(Str::slug($park['name'] . ' ' . ($park['city'] ?? $park['state'])), 180, '');
            if (IndustrialArea::where('slug', $slug)->exists()) $slug .= '-' . Str::lower(Str::random(4));
            IndustrialArea::create([
                'state_id' => $stateId, 'name' => $park['name'], 'slug' => $slug, 'city' => $park['city'], 'district' => $park['city'],
                'area_type' => $park['type'], 'sectors' => $park['type'] === 'it_park' ? ['IT Services', 'Software', 'BPO'] : ($park['type'] === 'sez' ? ['Export Units', 'IT Services', 'Manufacturing'] : ['Manufacturing', 'Engineering']),
                'latitude' => $park['lat'], 'longitude' => $park['lon'], 'source_name' => 'OpenStreetMap', 'source_url' => 'https://www.openstreetmap.org/' . $park['osm'],
                'is_active' => true, 'is_featured' => false, 'verification_status' => 'unverified',
            ]);
            $created++;
        }
        return ['companies' => count($parks), 'created' => $created, 'updated' => $updated];
    }

    private function type(string $name): string
    {
        if (preg_match('/\bSEZ\b|Special Economic Zone/i', $name)) return 'sez';
        if (preg_match('/' . self::PARK . '/i', $name)) return 'it_park';
        if (preg_match('/estate/i', $name)) return 'industrial_estate';
        return 'industrial_area';
    }

    private function nearestPlace(array $places, float $lat, float $lon): ?string
    {
        $best = null;
        $bestDistance = INF;
        foreach ($places as $place) {
            // Equirectangular distance is plenty to pick the nearest town; cities get a small preference.
            $distance = hypot(($place['lat'] - $lat), ($place['lon'] - $lon) * cos(deg2rad($lat))) * ($place['city'] ? 0.8 : 1);
            if ($distance < $bestDistance) { $bestDistance = $distance; $best = $place['name']; }
        }
        return $bestDistance < 0.6 ? $best : null; // ~65 km
    }
}
