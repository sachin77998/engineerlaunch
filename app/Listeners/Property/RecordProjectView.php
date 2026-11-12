<?php

namespace App\Listeners\Property;

use App\Events\Property\ProjectViewed;
use App\Models\Property\Project;
use Illuminate\Support\Facades\DB;

// Daily view counts per project drive "in high demand".
class RecordProjectView
{
    public function handle(ProjectViewed $event): void
    {
        $id = $event->project->id;
        if (!cache()->add('prv:' . $id . ':' . sha1($event->visitor) . ':' . today()->toDateString(), 1, now()->endOfDay())) return;
        Project::whereKey($id)->increment('views');
        DB::table('project_view_days')->upsert([['project_id' => $id, 'day' => today()->toDateString(), 'views' => 1]], ['project_id', 'day'], ['views' => DB::raw('views + 1')]);
    }
}
