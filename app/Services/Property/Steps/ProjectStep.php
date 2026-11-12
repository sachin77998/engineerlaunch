<?php

namespace App\Services\Property\Steps;

use App\Contracts\PropertyStep;
use App\Models\Property\Project;
use App\Models\Property\Property;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Link the listing to its project / building, so it shows on the project page with the building's photos.
// Only offered when the listing's city has projects.
class ProjectStep implements PropertyStep
{
    public function key(): string { return 'project'; }

    public function label(): string { return 'Project / building'; }

    public function icon(): string { return '🏙️'; }

    public function after(): string { return 'location'; }

    public function applies(Property $p): bool
    {
        return !$p->isLand() && !$p->isPg() && $p->city_slug && Project::live()->where('city_slug', $p->city_slug)->exists();
    }

    public function view(): string { return 'properties.post.steps.project'; }

    public function viewData(Property $p): array
    {
        return ['projects' => Project::live()->where('city_slug', $p->city_slug)->where('segment', $p->segment)->orderByRaw('locality = ? desc', [$p->locality])->orderBy('name')
            ->get(['id', 'name', 'locality'])];
    }

    public function save(Request $request, Property $p): void
    {
        $data = $request->validate(['project_id' => ['nullable', Rule::exists('projects', 'id')->where('city_slug', $p->city_slug)->where('is_active', true)]]);
        $p->project_id = $data['project_id'] ?? null;
        if ($p->project_id && !$p->society) $p->society = Project::whereKey($p->project_id)->value('name');
    }
}
