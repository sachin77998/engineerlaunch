<?php

namespace App\Events\Property;

use App\Models\Property\Property;

// A listing went live, changed while live, or left the market. $change: published | updated | removed.
class PropertyChanged implements BusEvent
{
    public function __construct(public Property $property, public string $change) {}

    public function topic(): string { return 'property.' . $this->change; }

    public function key(): ?string { return 'property:' . $this->property->id; }

    public function payload(): array
    {
        $p = $this->property;
        return ['id' => $p->id, 'slug' => $p->slug, 'status' => $p->status, 'listing' => $p->listing, 'segment' => $p->segment, 'kind' => $p->kind,
            'city_slug' => $p->city_slug, 'locality' => $p->locality, 'project_id' => $p->project_id, 'price' => $p->price_sort, 'area_sqft' => $p->area_sqft];
    }
}
