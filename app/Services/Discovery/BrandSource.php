<?php

namespace App\Services\Discovery;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Brands and chains that actually trade in India, found without a hand-made list:
 * - OpenStreetMap (ODbL): every mapped outlet with a "brand" tag in a state (McDonald's, Burger King, Metro, Mochi,
 *   Tanishq, petrol pumps, bank branches…), counted per brand.
 * - Name Suggestion Index (BSD): brands the OSM community records as present in India.
 */
class BrandSource
{
    public const NSI_URL = 'https://cdn.jsdelivr.net/npm/name-suggestion-index@latest/dist/nsi.min.json';

    // OSM key=value → words the CompanyClassifier understands.
    private const TAG_WORDS = [
        'amenity=fast_food' => 'quick service restaurant burger pizza', 'amenity=restaurant' => 'restaurant', 'amenity=cafe' => 'cafe coffee',
        'amenity=ice_cream' => 'ice cream', 'amenity=bank' => 'bank', 'amenity=atm' => 'bank', 'amenity=fuel' => 'petroleum fuel',
        'amenity=pharmacy' => 'pharmacy chemist medical store', 'amenity=hospital' => 'hospital healthcare', 'amenity=clinic' => 'healthcare clinic',
        'amenity=cinema' => 'cinema entertainment film', 'amenity=school' => 'school education', 'amenity=college' => 'college education',
        'amenity=charging_station' => 'electric vehicle charging', 'amenity=bureau_de_change' => 'forex exchange',
        'shop=shoes' => 'footwear shoes', 'shop=clothes' => 'apparel fashion clothing', 'shop=boutique' => 'apparel fashion',
        'shop=jewelry' => 'jewellery jewelry', 'shop=watches' => 'watches jewellery', 'shop=optician' => 'eyewear optician',
        'shop=mobile_phone' => 'mobile phone smartphone electronics', 'shop=electronics' => 'electronics appliance', 'shop=computer' => 'computer electronics',
        'shop=supermarket' => 'supermarket retail', 'shop=convenience' => 'retail grocery', 'shop=department_store' => 'department store retail',
        'shop=car' => 'automobile car dealer', 'shop=motorcycle' => 'motorcycle two-wheeler', 'shop=car_repair' => 'automobile service',
        'shop=tyres' => 'tyre', 'shop=car_parts' => 'auto parts', 'shop=bicycle' => 'bicycle cycles', 'shop=furniture' => 'furniture',
        'shop=paint' => 'paints', 'shop=hardware' => 'hardware building materials', 'shop=bakery' => 'bakery', 'shop=confectionery' => 'sweets confectionery',
        'shop=cosmetics' => 'beauty cosmetics', 'shop=beauty' => 'beauty salon', 'shop=hairdresser' => 'salon beauty', 'shop=chemist' => 'chemist personal care',
        'shop=books' => 'books publishing', 'shop=stationery' => 'stationery', 'shop=sports' => 'sports goods', 'shop=toys' => 'toys retail',
        'shop=alcohol' => 'beverages distillery', 'shop=beverages' => 'beverages', 'shop=dairy' => 'dairy milk', 'shop=fabric' => 'fabric textile',
        'shop=bag' => 'bags fashion', 'shop=gift' => 'gifts retail', 'shop=houseware' => 'home appliances', 'shop=appliance' => 'appliance',
        'shop=money_lender' => 'finance loan', 'shop=travel_agency' => 'travel tourism', 'tourism=hotel' => 'hotel hospitality',
        'office=insurance' => 'insurance', 'office=telecommunication' => 'telecom', 'office=company' => '', 'shop=wholesale' => 'wholesale retail',
    ];

    /** Brands with outlets in one Indian state (OpenStreetMap). */
    public function fromOpenStreetMap(string $state): array
    {
        $codes = ParkSource::STATES[$state] ?? throw new RuntimeException("Unknown state {$state}");
        $area = implode('', array_map(fn ($code) => "area[\"ISO3166-2\"=\"{$code}\"];", $codes));
        $query = "[out:json][timeout:170];({$area})->.s;nwr[\"brand\"](area.s);out tags;";
        $response = null;
        foreach ((array) config('discovery.overpass_urls', [config('discovery.overpass_url', 'https://overpass-api.de/api/interpreter'), 'https://overpass.kumi.systems/api/interpreter']) as $url) {
            $response = Http::withHeaders(['User-Agent' => config('discovery.user_agent')])->asForm()->timeout(190)->post($url, ['data' => $query]);
            if ($response->successful()) break;
        }
        if (!$response || !$response->successful()) throw new RuntimeException('Overpass returned HTTP ' . ($response?->status() ?? 0));

        $brands = [];
        foreach ($response->json('elements', []) as $element) {
            $tags = $element['tags'] ?? [];
            $name = trim($tags['brand:en'] ?? $tags['brand'] ?? '');
            if ($name === '' || mb_strlen($name) > 120 || !preg_match('/\p{Latin}/u', $name)) continue;
            $kind = $this->kind($tags);
            $key = mb_strtolower($name);
            $brands[$key] ??= ['name' => $name, 'outlets' => 0, 'kinds' => [], 'wikidata' => null, 'website' => null, 'operator' => null, 'cities' => []];
            $brands[$key]['outlets']++;
            if ($kind) $brands[$key]['kinds'][$kind] = ($brands[$key]['kinds'][$kind] ?? 0) + 1;
            $brands[$key]['wikidata'] ??= $tags['brand:wikidata'] ?? null;
            $brands[$key]['website'] ??= $this->website($tags['brand:website'] ?? $tags['website'] ?? null);
            $brands[$key]['operator'] ??= $tags['operator'] ?? null;
            if ($city = $tags['addr:city'] ?? null) $brands[$key]['cities'][$city] = true;
        }
        return array_values(array_map(function ($brand) use ($state) {
            arsort($brand['kinds']);
            $kind = array_key_first($brand['kinds']);
            $words = $kind ? (self::TAG_WORDS[$kind] ?? str_replace(['=', '_'], ' ', $kind)) : '';
            $cities = array_slice(array_keys($brand['cities']), 0, 6);
            return array_filter([
                'name' => $brand['name'], 'website' => $brand['website'], 'country' => 'India',
                'industries' => array_values(array_filter([$words])),
                'products' => trim(($words ? ucfirst($words) . ' · ' : '') . $brand['outlets'] . ' outlet' . ($brand['outlets'] === 1 ? '' : 's') . " in {$state}" . ($cities ? ' (' . implode(', ', $cities) . ')' : '')),
                'description' => $brand['operator'] ? 'Outlets operated by ' . $brand['operator'] . '.' : null,
                'source_url' => $brand['wikidata'] ? 'https://www.wikidata.org/wiki/' . $brand['wikidata'] : 'https://www.openstreetmap.org/',
            ], fn ($v) => $v !== null && $v !== '');
        }, $brands));
    }

    /** Brands the Name Suggestion Index lists for India (whole country or an Indian state). */
    public function fromNameSuggestionIndex(): array
    {
        $index = Cache::remember('discovery:nsi-india', now()->addDays(7), function () {
            $response = Http::withHeaders(['User-Agent' => config('discovery.user_agent')])->timeout(120)->get(self::NSI_URL);
            if (!$response->successful()) throw new RuntimeException('Name Suggestion Index returned HTTP ' . $response->status());
            $rows = [];
            foreach ($response->json('nsi', []) as $path => $group) {
                if (!str_starts_with($path, 'brands/') && !str_starts_with($path, 'operators/')) continue;
                $kind = implode('=', array_slice(explode('/', $path), 1, 2));
                foreach ($group['items'] ?? [] as $item) {
                    // Entries are country/region codes, or [lon, lat, radius] circles, which are skipped.
                    $include = array_map(fn ($code) => is_string($code) ? strtolower($code) : '', $item['locationSet']['include'] ?? []);
                    if (!collect($include)->contains(fn ($code) => $code === 'in' || str_starts_with($code, 'in-'))) continue;
                    $tags = $item['tags'] ?? [];
                    $name = $tags['brand'] ?? $tags['operator'] ?? $tags['name'] ?? null;
                    if (!$name) continue;
                    $rows[] = ['name' => $name, 'kind' => $kind, 'wikidata' => $tags['brand:wikidata'] ?? $tags['operator:wikidata'] ?? null];
                }
            }
            return $rows;
        });
        $companies = [];
        foreach ($index as $row) {
            $words = self::TAG_WORDS[$row['kind']] ?? str_replace(['=', '_'], ' ', $row['kind']);
            $companies[mb_strtolower($row['name'])] ??= array_filter([
                'name' => $row['name'], 'country' => 'India', 'industries' => [$words], 'products' => ucfirst($words) . ' brand in India',
                'source_url' => $row['wikidata'] ? 'https://www.wikidata.org/wiki/' . $row['wikidata'] : 'https://nsi.guide/',
            ]);
        }
        return array_values($companies);
    }

    private function kind(array $tags): ?string
    {
        foreach (['amenity', 'shop', 'tourism', 'office', 'craft', 'leisure', 'healthcare'] as $key) {
            if (isset($tags[$key])) return $key . '=' . $tags[$key];
        }
        return null;
    }

    private function website(?string $url): ?string
    {
        if (!$url || !preg_match('~^https?://[^/\s]+~i', $url, $m)) return null;
        return strlen($m[0]) <= 255 ? rtrim($m[0], '/') : null;
    }
}
