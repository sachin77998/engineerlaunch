<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;

// "Projects by possession date": projects grouped by possession.
class PossessionDate extends CitySection
{
    public int $minutes = 30;

    public function data(CityContext $c, array $options): ?array
    {
        $year = (int) date('Y');
        $rows = $c->projects()->selectRaw("case when status = 'ready' or possession_on <= ? then 'ready' when possession_on is null then null when year(possession_on) <= ? then year(possession_on) else 'later' end as b, count(*) as n",
            [today()->toDateString(), $year + 2])->groupBy('b')->pluck('n', 'b');
        $labels = ['ready' => 'Ready to move'];
        foreach (range($year, $year + 2) as $y) $labels[$y] = 'Possession in ' . $y;
        $labels['later'] = 'Possession ' . ($year + 3) . ' or later';
        $buckets = collect($labels)
            ->map(fn ($label, $key) => ['label' => $label, 'count' => (int) ($rows[$key] ?? 0), 'url' => route('projects.index', [$c->slug(), 'possession' => $key])])
            ->filter(fn ($b) => $b['count'] > 0)->values();
        return $buckets->isEmpty() ? null : ['buckets' => $buckets];
    }
}
