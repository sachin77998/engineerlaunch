<?php

namespace App\Events\Property;

// A visitor ran a property search. Feeds "recent searches", "continue browsing" and "demand in <city>".
class PropertySearched implements BusEvent
{
    public function __construct(public string $visitor, public ?int $userId, public string $mode, public ?string $citySlug, public ?string $locality,
        public ?string $homeType, public string $label, public string $url) {}

    public function topic(): string { return 'property.searched'; }

    public function key(): ?string { return $this->citySlug ? 'city:' . $this->citySlug : null; }

    public function payload(): array
    {
        return ['mode' => $this->mode, 'city_slug' => $this->citySlug, 'locality' => $this->locality, 'home_type' => $this->homeType, 'visitor' => hash('sha256', $this->visitor)];
    }
}
