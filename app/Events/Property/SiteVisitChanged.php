<?php

namespace App\Events\Property;

use App\Models\Property\SiteVisit;

// A site visit was booked, rescheduled, confirmed, completed, missed or cancelled. $change is one of those words; $by is visitor | host.
class SiteVisitChanged implements BusEvent
{
    public function __construct(public SiteVisit $visit, public string $change, public string $by = 'visitor') {}

    public function topic(): string { return 'site_visit.' . $this->change; }

    public function key(): ?string { return $this->visit->visitable_type . ':' . $this->visit->visitable_id; }

    public function payload(): array
    {
        $v = $this->visit;
        return ['id' => $v->id, 'visitable_type' => $v->visitable_type, 'visitable_id' => $v->visitable_id, 'visit_at' => $v->visit_at->toIso8601String(),
            'status' => $v->status, 'people' => $v->people, 'by' => $this->by];
    }
}
