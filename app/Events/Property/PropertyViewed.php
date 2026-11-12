<?php

namespace App\Events\Property;

use App\Models\Property\Property;

class PropertyViewed implements BusEvent
{
    public function __construct(public Property $property, public string $visitor, public ?int $userId) {}

    public function topic(): string { return 'property.viewed'; }

    public function key(): ?string { return 'property:' . $this->property->id; }

    public function payload(): array
    {
        return ['property_id' => $this->property->id, 'visitor' => hash('sha256', $this->visitor), 'city_slug' => $this->property->city_slug, 'listing' => $this->property->listing];
    }
}
