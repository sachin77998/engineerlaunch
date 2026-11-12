<?php

namespace App\Listeners\Property;

use App\Events\Property\ProjectChanged;
use App\Events\Property\PropertyChanged;
use App\Jobs\FetchNearbyPlaces;
use App\Models\Property\NearbyPlace;

// When something with a map pin goes live, fetch what is around it (once per ~100 m spot, refreshed every 90 days).
class QueueNearbyPlaces
{
    public function handle(PropertyChanged|ProjectChanged $event): void
    {
        $model = $event instanceof PropertyChanged ? $event->property : $event->project;
        if ($event->change === 'removed' || !$key = NearbyPlace::geoKey($model->latitude, $model->longitude)) return;
        if (NearbyPlace::where('geo_key', $key)->where('fetched_at', '>', now()->subDays(90))->exists()) return;
        if (cache()->add('nearby-queued:' . $key, 1, now()->addHour())) FetchNearbyPlaces::dispatch((float) $model->latitude, (float) $model->longitude);
    }
}
