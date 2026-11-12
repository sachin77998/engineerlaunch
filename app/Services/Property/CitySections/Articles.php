<?php

namespace App\Services\Property\CitySections;

use App\Models\NewsArticle;
use App\Services\Property\CityContext;

// "Property news and guides": one tab per news category in options.tabs, articles naming the city first.
class Articles extends CitySection
{
    public int $minutes = 60;

    public function data(CityContext $c, array $options): ?array
    {
        $names = array_filter(array_unique([$c->city->name, ...(array) ($options['aliases'][$c->city->name] ?? [])]));
        $tabs = collect($options['tabs'] ?? [])->map(function ($slug) use ($names) {
            return NewsArticle::where('is_published', true)->whereHas('category', fn ($q) => $q->where('slug', $slug))
                ->orderByRaw('(' . implode(' or ', array_fill(0, count($names), 'title like ?')) . ') desc', array_map(fn ($n) => '%' . $n . '%', $names))
                ->latest('published_at')->limit(8)->get(['id', 'title', 'slug', 'image_url', 'published_at']);
        })->filter->isNotEmpty();
        return $tabs->isEmpty() ? null : ['tabs' => $tabs];
    }
}
