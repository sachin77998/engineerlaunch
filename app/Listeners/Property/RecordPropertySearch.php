<?php

namespace App\Listeners\Property;

use App\Events\Property\PropertySearched;
use Illuminate\Support\Facades\DB;

// Keeps a visitor's searches (newest replaces an identical older one) for "recent searches" and city demand.
class RecordPropertySearch
{
    public function handle(PropertySearched $e): void
    {
        DB::table('property_search_logs')->where(['visitor' => $e->visitor, 'url' => $e->url])->delete();
        DB::table('property_search_logs')->insert(['visitor' => $e->visitor, 'user_id' => $e->userId, 'mode' => $e->mode, 'city_slug' => $e->citySlug,
            'locality' => $e->locality, 'home_type' => $e->homeType, 'label' => mb_substr($e->label, 0, 191), 'url' => mb_substr($e->url, 0, 600), 'created_at' => now()]);
    }
}
