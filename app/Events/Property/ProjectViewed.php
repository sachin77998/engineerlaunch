<?php

namespace App\Events\Property;

use App\Models\Property\Project;

class ProjectViewed implements BusEvent
{
    public function __construct(public Project $project, public string $visitor) {}

    public function topic(): string { return 'project.viewed'; }

    public function key(): ?string { return 'project:' . $this->project->id; }

    public function payload(): array
    {
        return ['project_id' => $this->project->id, 'visitor' => hash('sha256', $this->visitor), 'city_slug' => $this->project->city_slug];
    }
}
