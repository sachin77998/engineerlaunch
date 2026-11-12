<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;
use Illuminate\Support\Facades\DB;

// The hero search: tabs (Buy, Rent, New launch, Commercial, Plots, Projects, Post property), a property-type picker per tab,
// locality suggestions, locate-me and voice input, plus the visitor's recent searches.
class SearchBox extends CitySection
{
    public int $minutes = 0;

    public function data(CityContext $c, array $options): ?array
    {
        $o = config('properties');
        $residential = collect($o['home_types'])->map(fn ($t) => $t[0])->all();
        $commercial = $o['kinds']['commercial'] ?? [];
        $slug = $c->slug();
        // Each tab: label, base URL, the field the type picker fills, its choices, fixed extra query.
        $tabs = [
            'buy' => ['Buy', $c->search('buy'), 'type', ['' => 'All residential'] + $residential, []],
            'rent' => ['Rent', $c->search('rent'), 'type', ['' => 'All residential'] + $residential + ['kind:pg' => 'PG / co-living'], []],
            'new' => ['New launch', $c->search('buy'), 'type', ['' => 'All residential'] + $residential, ['new_projects' => 1]],
            'commercial' => ['Commercial', $c->search('commercial-buy'), 'kind', ['' => 'All commercial'] + $commercial, []],
            'plots' => ['Plots / land', $c->search('buy'), null, [], ['type' => ['plot']]],
            'projects' => ['Projects', route('projects.index', $slug), null, [], []],
        ];
        $recent = DB::table('property_search_logs')->whereIn('visitor', $c->visitorIds)->latest('created_at')->limit(3)->get(['label', 'url']);
        return ['tabs' => $tabs, 'recent' => $recent];
    }
}
