<?php

namespace App\Jobs;

use App\Models\Property\Property;
use App\Services\Property\PropertyAlerts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

// Runs saved-search matching for a listing that just went live, off the request.
class SendInstantPropertyAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(public int $propertyId) {}

    public function handle(PropertyAlerts $alerts): void
    {
        if ($p = Property::find($this->propertyId)) $alerts->instant($p);
    }
}
