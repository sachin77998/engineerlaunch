<?php

namespace App\Events\Property;

use App\Models\Property\Project;

class ProjectChanged implements BusEvent
{
    public function __construct(public Project $project, public string $change) {}

    public function topic(): string { return 'project.' . $this->change; }

    public function key(): ?string { return 'project:' . $this->project->id; }

    public function payload(): array
    {
        $p = $this->project;
        return ['id' => $p->id, 'slug' => $p->slug, 'name' => $p->name, 'city_slug' => $p->city_slug, 'status' => $p->status, 'is_featured' => $p->is_featured, 'is_active' => $p->is_active];
    }
}
