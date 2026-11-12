<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;
use Illuminate\Support\Facades\DB;

// Sidebar "Your recent activity": listings viewed, searches and (signed in) shortlisted.
class GuestActivity extends CitySection
{
    public int $minutes = 0;

    public function data(CityContext $c, array $options): ?array
    {
        $since = now()->subDays(30);
        return [
            'viewed' => DB::table('property_view_logs')->whereIn('visitor', $c->visitorIds)->where('created_at', '>', $since)->distinct()->count('property_id'),
            'searched' => DB::table('property_search_logs')->whereIn('visitor', $c->visitorIds)->where('created_at', '>', $since)->count(),
            'shortlisted' => auth()->check() ? DB::table('property_shortlists')->where('user_id', auth()->id())->count() : null,
        ];
    }
}
