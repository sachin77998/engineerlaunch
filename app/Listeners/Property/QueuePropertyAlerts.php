<?php

namespace App\Listeners\Property;

use App\Events\Property\PropertyChanged;
use App\Jobs\SendInstantPropertyAlerts;

// A listing went live → check it against "instant" saved searches.
class QueuePropertyAlerts
{
    public function handle(PropertyChanged $event): void
    {
        if ($event->change === 'published') SendInstantPropertyAlerts::dispatch($event->property->id);
    }
}
