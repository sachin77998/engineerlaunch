<?php

namespace App\Observers;

use App\Events\Property\PropertyChanged;
use App\Models\Property\Property;

// Turns listing saves into domain events: published (went live), updated (changed while live), removed (left the market).
class PropertyObserver
{
    public function created(Property $p): void
    {
        if ($p->status === 'active') event(new PropertyChanged($p, 'published'));
    }

    public function updated(Property $p): void
    {
        $was = $p->getOriginal('status');
        if ($p->wasChanged('status') && $p->status === 'active') event(new PropertyChanged($p, 'published'));
        elseif ($p->wasChanged('status') && $was === 'active') event(new PropertyChanged($p, 'removed'));
        elseif ($p->status === 'active' && array_diff(array_keys($p->getChanges()), ['updated_at', 'views'])) event(new PropertyChanged($p, 'updated'));
    }

    public function deleted(Property $p): void
    {
        if ($p->getOriginal('status') === 'active') event(new PropertyChanged($p, 'removed'));
    }
}
