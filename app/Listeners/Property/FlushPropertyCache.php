<?php

namespace App\Listeners\Property;

use App\Events\Property\ProjectChanged;
use App\Events\Property\PropertyChanged;
use App\Services\Property\PropertyCache;
use Illuminate\Support\Facades\Cache;

class FlushPropertyCache
{
    public function handle(PropertyChanged|ProjectChanged $event): void
    {
        $model = $event instanceof PropertyChanged ? $event->property : $event->project;
        PropertyCache::bump($model->city_slug);
        if ($model->getOriginal('city_slug') && $model->getOriginal('city_slug') !== $model->city_slug) PropertyCache::bump($model->getOriginal('city_slug'));
        Cache::forget('properties:counts');
    }
}
