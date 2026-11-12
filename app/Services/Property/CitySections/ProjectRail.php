<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;
use Illuminate\Support\Facades\DB;

// A row of projects. options.order: demand (most viewed in 30 days) | featured (handpicked by our team) | new (newly launched).
class ProjectRail extends CitySection
{
    public function data(CityContext $c, array $options): ?array
    {
        $q = $c->projects()->with(['images', 'builder:id,name,slug']);
        match ($options['order'] ?? 'new') {
            'demand' => $q->joinSub(DB::table('project_view_days')->where('day', '>=', today()->subDays(30))->groupBy('project_id')->selectRaw('project_id, sum(views) as recent'), 'd', 'd.project_id', '=', 'projects.id')
                ->orderByDesc('d.recent')->select('projects.*'),
            'featured' => $q->where('is_featured', true)->latest('updated_at'),
            default => $q->where(fn ($w) => $w->where('status', 'new_launch')->orWhere('launched_on', '>=', today()->subYear()))->orderByDesc('launched_on')->latest(),
        };
        $items = $q->limit(10)->get();
        return $items->isEmpty() ? null : ['items' => $items];
    }
}
