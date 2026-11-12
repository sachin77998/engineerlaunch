<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;
use Illuminate\Support\Facades\Route;

// "Our property services": shortcuts from options.items ([title, hint, icon, route, query]), skipping routes that don't exist.
class ExploreServices extends CitySection
{
    public int $minutes = 0;

    public function data(CityContext $c, array $options): ?array
    {
        $items = collect($options['items'] ?? [])->filter(fn ($i) => Route::has($i[3]))->map(function ($i) use ($c) {
            $params = str_starts_with($i[3], 'properties.search') ? [$i[4]['mode'] ?? 'buy', $c->slug()] : [];
            $query = array_diff_key($i[4] ?? [], ['mode' => 1]);
            return ['title' => $i[0], 'hint' => $i[1], 'icon' => $i[2], 'url' => route($i[3], $params) . ($query ? '?' . http_build_query($query) : '')];
        })->values();
        return $items->isEmpty() ? null : ['items' => $items];
    }
}
