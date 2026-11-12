<?php

namespace App\Listeners\Property;

use App\Events\Property\PropertyViewed;
use Illuminate\Support\Facades\DB;

// One row per visitor per listing per day: "your recent activity", "continue browsing" and recommendations read it.
class RecordPropertyView
{
    public function handle(PropertyViewed $event): void
    {
        $p = $event->property;
        if (!cache()->add('pvl:' . $p->id . ':' . sha1($event->visitor) . ':' . today()->toDateString(), 1, now()->endOfDay())) return;
        DB::table('property_view_logs')->insert(['visitor' => $event->visitor, 'user_id' => $event->userId, 'property_id' => $p->id, 'city_slug' => $p->city_slug,
            'listing' => $p->listing, 'home_type' => $p->building_type ?: $p->kind, 'price' => $p->price_sort, 'created_at' => now()]);
    }
}
