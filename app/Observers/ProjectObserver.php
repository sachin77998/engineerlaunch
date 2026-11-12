<?php

namespace App\Observers;

use App\Events\Property\ProjectChanged;
use App\Models\Property\Project;

class ProjectObserver
{
    public function created(Project $p): void
    {
        if ($p->is_active !== false) event(new ProjectChanged($p, 'published'));
    }

    public function updated(Project $p): void
    {
        if (!array_diff(array_keys($p->getChanges()), ['updated_at', 'views'])) return;
        event(new ProjectChanged($p, $p->is_active ? ($p->wasChanged('is_active') ? 'published' : 'updated') : 'removed'));
    }

    public function deleted(Project $p): void
    {
        event(new ProjectChanged($p, 'removed'));
    }
}
