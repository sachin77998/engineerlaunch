<?php

namespace App\Console\Commands;

use App\Models\Property\PropertyCity;
use App\Services\Property\PropertyCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

// Photo and map position for each city from its Wikipedia article: the lead image (Wikimedia Commons) for the
// "popular cities" tiles where an admin hasn't uploaded one, and the coordinates "locate me" uses to find the nearest city.
class FetchCityImages extends Command
{
    protected $signature = 'property-cities:images {--force : refetch cities that already have an image and coordinates}';
    protected $description = 'Fetch a Wikipedia photo and coordinates for property cities missing them';

    public function handle(): int
    {
        $cities = PropertyCity::when(!$this->option('force'), fn ($q) => $q->where(fn ($w) => $w->where(fn ($x) => $x->whereNull('image_path')->whereNull('image_url'))->orWhereNull('latitude')))->get();
        foreach ($cities as $city) {
            $hit = null;
            foreach (array_unique([$city->name, trim($city->name . ', ' . $city->state, ', '), $city->name . ' city']) as $title) {
                $res = Http::withHeaders(['User-Agent' => config('discovery.user_agent', 'naukri-com')])->timeout(20)
                    ->get('https://en.wikipedia.org/api/rest_v1/page/summary/' . rawurlencode(str_replace(' ', '_', $title)));
                if ($res->successful() && $res->json('type') !== 'disambiguation' && ($res->json('thumbnail.source') || $res->json('coordinates.lat'))) { $hit = $res; break; }
            }
            $changes = [];
            if ($hit && !$city->image_path && ($url = $hit->json('thumbnail.source')) && ($this->option('force') || !$city->image_url)) {
                // Ask Commons for a 640 px wide rendition instead of the default small thumbnail.
                $changes['image_url'] = preg_replace('~/(\d+)px-~', '/640px-', $url);
            }
            if ($hit && $hit->json('coordinates.lat') && ($this->option('force') || $city->latitude === null)) {
                $changes += ['latitude' => $hit->json('coordinates.lat'), 'longitude' => $hit->json('coordinates.lon')];
            }
            if ($changes) $city->update($changes);
            $this->line(($changes ? 'ok   ' : 'none ') . $city->name . ($changes ? ' (' . implode(', ', array_keys($changes)) . ')' : ''));
            usleep(200000);
        }
        PropertyCache::bump('_all');
        return self::SUCCESS;
    }
}
