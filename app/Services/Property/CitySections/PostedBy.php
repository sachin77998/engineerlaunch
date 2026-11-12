<?php

namespace App\Services\Property\CitySections;

use App\Services\Property\CityContext;

// "Listings by advertiser": dealer, owner and builder listing counts.
class PostedBy extends CitySection
{
    public function data(CityContext $c, array $options): ?array
    {
        $counts = $c->listings()->join('property_owners', 'property_owners.id', '=', 'properties.owner_id')
            ->groupBy('property_owners.owner_type')->selectRaw('property_owners.owner_type as t, count(*) as n')->pluck('n', 't');
        $map = ['agent' => ['dealer', 'Dealer', '🧑‍💼'], 'owner' => ['owner', 'Owner', '🏠'], 'builder' => ['builder', 'Builder', '🏗️']];
        $rows = collect($map)->map(fn ($m, $type) => ['label' => $m[1], 'icon' => $m[2], 'count' => (int) ($counts[$type] ?? 0), 'url' => $c->search('buy', ['posted_by' => [$m[0]]])])
            ->filter(fn ($r) => $r['count'] > 0)->values();
        return $rows->isEmpty() ? null : ['rows' => $rows];
    }
}
