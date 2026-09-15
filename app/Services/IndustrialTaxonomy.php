<?php

namespace App\Services;

use App\Models\IndustrialJobRole;
use App\Models\IndustrialProcess;
use App\Models\IndustrialSector;
use App\Models\IndustrialSubsector;
use Illuminate\Validation\ValidationException;

class IndustrialTaxonomy
{
    public function prepare(array $filters): array
    {
        $sectors = IndustrialSector::where('is_active', true)->orderBy('name')->get();
        $sector = $sectors->firstWhere('id', $filters['sector'] ?? null);
        if (! empty($filters['sector']) && ! $sector) {
            $this->invalid('sector');
        }
        $subs = $sector ? IndustrialSubsector::where('sector_id', $sector->id)->where('is_active', true)->get() : collect();
        $sub = $subs->firstWhere('id', $filters['subsector'] ?? null);
        if (! empty($filters['subsector']) && ! $sub) {
            $this->invalid('subsector');
        }
        $ids = $sub ? collect([$sub->id]) : $subs->pluck('id');
        if ($sub) {
            do {
                $before = $ids->count();
                $ids = $ids->merge($subs->whereIn('parent_id', $ids)->pluck('id'))->unique()->values();
            } while ($before !== $ids->count());
        }
        $processes = $sector ? IndustrialProcess::where('is_active', true)->whereHas('subsectors', fn ($q) => $q->whereIn('industrial_subsectors.id', $ids))->orderBy('name')->get() : collect();
        $process = $processes->firstWhere('id', $filters['process'] ?? null);
        if (! empty($filters['process']) && ! $process) {
            $this->invalid('process');
        }
        $dictionary = IndustrialJobRole::where('is_active', true)->whereHas('department', fn ($q) => $q->where('is_active', true))->with('department', 'aliases', 'profile', 'processes', 'sectors');
        if ($process) {
            $dictionary->whereHas('processes', fn ($q) => $q->whereKey($process->id));
        } elseif ($sector) {
            $dictionary->where(fn($q)=>$q->whereHas('sectors',fn($s)=>$s->whereKey($sector->id))->orWhereHas('processes', fn ($p) => $p->whereIn('industrial_processes.id', $processes->pluck('id'))));
        }
        $careerMatch=app(IndustrialCareerMatcher::class)->match(app(IndustrialSearch::class)->tokens($filters['search']??''));
        if (!empty($filters['search'])) {
            if($careerMatch['matches']) $dictionary->whereIn('id',array_keys($careerMatch['matches']));
            else {$term='%'.$filters['search'].'%';$dictionary->where(fn($q)=>$q->where('name','like',$term)->orWhereHas('aliases',fn($a)=>$a->where('name','like',$term)));}
        }
        $dictionary=$dictionary->orderBy('name')->get()->sortByDesc(fn($role)=>$careerMatch['matches'][$role->id]['score']??0)->values();
        $subOptions = $subs->map(function ($node) use ($subs) {
        $names = [$node->name];
        $parent = $node->parent_id;
        $seen = [$node->id];
        while ($parent && ! in_array($parent, $seen)) {
        $ancestor = $subs->firstWhere('id', $parent);
        if (! $ancestor) {
        break;
        }$seen[] = $parent;
        array_unshift($names, $ancestor->name);
        $parent = $ancestor->parent_id;
        }

return ['id' => $node->id, 'name' => implode(" \u{2192} ", $names)];
        })->sortBy('name')->values();
        $theme = $sector;
        if($sub?->visual_sector_id)$theme=$sectors->firstWhere('id',$sub->visual_sector_id)??$sector;
        if (! $sector) {
            $theme = $sectors->firstWhere('slug', 'steel');
        }
        $gallery = $theme ? $theme->assets : collect();
        $heroAsset=$gallery->firstWhere('asset_type','hero') ?? $gallery->firstWhere('path', $theme?->hero_image);
        $heroUrl=$heroAsset?->url;
        $steps = $sub ? $sub->processes()->where('is_active', true)->get() : collect();

        return compact('sectors', 'sector', 'sub', 'ids', 'processes', 'process', 'dictionary', 'subOptions', 'theme', 'gallery', 'steps', 'heroAsset', 'heroUrl', 'careerMatch');
    }

    public function companies($query, array $t): void
    {
        if ($t['sector']) {
            $query->whereHas('sectors', fn ($q) => $q->whereKey($t['sector']->id));
        }
        if ($t['sub']) {
            $query->whereHas('subsectors', fn ($q) => $q->whereIn('industrial_subsectors.id', $t['ids']));
        }
        if ($t['process']) {
            $query->whereHas('processes', fn ($q) => $q->whereKey($t['process']->id));
        }
    }

    private function invalid($field): void
    {
        throw ValidationException::withMessages([$field => 'Choose an active '.$field.' within the selected sector hierarchy.']);
    }
}
