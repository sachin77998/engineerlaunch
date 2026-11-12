<?php

namespace App\Services\Property\CitySections;

use App\Models\Property\Property;
use App\Services\Property\CityContext;
use Illuminate\Support\Facades\DB;

// Listings the visitor looked at recently, newest first, this city's first.
class ContinueBrowsing extends CitySection
{
    public int $minutes = 0;

    public function data(CityContext $c, array $options): ?array
    {
        $ids = DB::table('property_view_logs')->whereIn('visitor', $c->visitorIds)->where('created_at', '>', now()->subDays(60))
            ->orderByRaw('city_slug = ? desc', [$c->slug()])->latest('created_at')->limit(30)->pluck('property_id')->unique()->take(8)->values();
        if ($ids->isEmpty()) return null;
        $items = Property::live()->whereIn('id', $ids)->with(['media', 'rentTerms', 'saleTerms', 'pgTerms', 'owner:id,owner_type,is_featured'])->get()->sortBy(fn ($p) => $ids->search($p->id))->values();
        return $items->isEmpty() ? null : ['items' => $items];
    }
}
