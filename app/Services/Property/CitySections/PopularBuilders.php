<?php

namespace App\Services\Property\CitySections;

use App\Models\Property\Builder;
use App\Services\Property\CityContext;

// Builders with the most projects in the city, with their project count everywhere.
class PopularBuilders extends CitySection
{
    public int $minutes = 60;

    public function data(CityContext $c, array $options): ?array
    {
        $inCity = $c->projects()->whereNotNull('builder_id')->groupBy('builder_id')->selectRaw('builder_id, count(*) as n')->pluck('n', 'builder_id');
        if ($inCity->isEmpty()) return null;
        $items = Builder::where('is_active', true)->whereIn('id', $inCity->keys())->withCount(['projects' => fn ($q) => $q->where('is_active', true)])->get()
            ->map(fn ($b) => ['builder' => $b, 'in_city' => (int) $inCity[$b->id]])->sortByDesc('in_city')->take($options['limit'] ?? 12)->values();
        return ['items' => $items];
    }
}
