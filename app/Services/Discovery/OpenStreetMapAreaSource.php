<?php

namespace App\Services\Discovery;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/** Named factories, offices and hotels inside an industrial estate or tech park, from OpenStreetMap (ODbL). */
class OpenStreetMapAreaSource
{
    private const GENERIC = '~^(sector|phase|block|plot|gate|road|industrial (area|estate|zone)|warehouse|factory|office|building|parking|shed|unit|plant)\b|^\W*$~i';

    public function fetch(array $hub): array
    {
        [$lat, $lon] = isset($hub['lat'], $hub['lon']) ? [$hub['lat'], $hub['lon']] : $this->geocode($hub);
        $around = sprintf('around:%d,%F,%F', config('discovery.radius'), $lat, $lon);
        $query = "[out:json][timeout:90];("
            . "nwr($around)[\"name\"][\"man_made\"=\"works\"];"
            . "nwr($around)[\"name\"][\"industrial\"];"
            . "nwr($around)[\"name\"][\"office\"~\"company|it|financial|insurance|telecommunication\"];"
            . "nwr($around)[\"name\"][\"building\"~\"industrial|factory|office\"];"
            . "nwr($around)[\"name\"][\"landuse\"=\"industrial\"];"
            . "nwr($around)[\"name\"][\"tourism\"=\"hotel\"];"
            . ");out tags center 1500;";
        $response = Http::withHeaders(['User-Agent' => config('discovery.user_agent')])->asForm()
            ->timeout(120)->retry(3, 15000)->post(config('discovery.overpass_url'), ['data' => $query]);
        if (!$response->successful()) throw new RuntimeException('Overpass returned HTTP ' . $response->status());
        if ($remark = $response->json('remark')) throw new RuntimeException('Overpass: ' . $remark);

        $companies = [];
        foreach ($response->json('elements', []) as $element) {
            $tags = $element['tags'] ?? [];
            $name = trim(preg_replace('/\s+/', ' ', $tags['name:en'] ?? $tags['name'] ?? ''));
            if (mb_strlen($name) < 3 || preg_match(self::GENERIC, $name) || Str::lower($name) === Str::lower($hub['name'])) continue;
            $key = Str::slug($name);
            $website = $tags['website'] ?? $tags['contact:website'] ?? null;
            if ($website && !preg_match('~^https?://~i', $website)) $website = 'https://' . ltrim($website, '/');
            $companies[$key] = [
                'name' => $name,
                'website' => $website && strlen($website) <= 255 ? rtrim($website, '/') : ($companies[$key]['website'] ?? null),
                'country' => 'India',
                'products' => $tags['product'] ?? $tags['industrial'] ?? $tags['office'] ?? null,
                'tags' => implode(' ', array_filter([$tags['industrial'] ?? null, $tags['man_made'] ?? null, $tags['office'] ?? null, $tags['product'] ?? null, $tags['tourism'] ?? null, $tags['operator'] ?? null])),
                'source_url' => 'https://www.openstreetmap.org/' . $element['type'] . '/' . $element['id'],
            ];
        }
        return array_values($companies);
    }

    private function geocode(array $hub): array
    {
        $response = Http::withHeaders(['User-Agent' => config('discovery.user_agent')])->timeout(30)
            ->get(config('discovery.nominatim_url'), ['q' => implode(', ', array_filter([$hub['name'], $hub['city'] ?? null, $hub['state'] ?? null, 'India'])), 'format' => 'json', 'limit' => 1]);
        $place = $response->json(0);
        if (!$place) throw new RuntimeException('Could not locate ' . $hub['name'] . ' on OpenStreetMap.');
        usleep(1100000); // Nominatim usage policy: at most one request per second.
        return [(float) $place['lat'], (float) $place['lon']];
    }
}
