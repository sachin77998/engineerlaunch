<?php

namespace App\Services;

class IndustrialSearch
{
    public function tokens(string $search): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}+.]+/u', mb_strtolower(trim($search)), -1, PREG_SPLIT_NO_EMPTY);

        return array_slice(array_values(array_unique(array_diff($tokens, ['job', 'jobs', 'opening', 'openings', 'near', 'in', 'at', 'for', 'the']))), 0, 12);
    }

    public function jobs($query, array $tokens): void
    {
        $match = app(IndustrialCareerMatcher::class)->match($tokens);
        if ($match['matches']) {
            $ids = array_keys($match['matches']);
            $query->where(function ($q) use ($ids, $tokens) {
                $q->whereIn('job_role_id', $ids)->orWhere(function ($literal) use ($tokens) { $this->literalJobs($literal, $tokens); });
            });
            $tokens = $match['remaining'];
        }
        $this->literalJobs($query, $tokens);
    }

    private function literalJobs($query, array $tokens): void
    {
        foreach ($tokens as $token) {
            $like = '%'.$token.'%';
            $query->where(function ($q) use ($like) {
                $q->where('job_title', 'like', $like)->orWhere('qualification', 'like', $like)->orWhere('skills', 'like', $like)
                    ->orWhereHas('company', fn ($c) => $c->visible()->where(fn ($n) => $n->where('name', 'like', $like)->orWhere('plant_name', 'like', $like)))
                    ->orWhereHas('area', fn ($a) => $this->areaText($a, $like))
                    ->orWhereHas('department', fn ($d) => $d->where('name', 'like', $like))
                    ->orWhereHas('role', fn ($r) => $r->where(fn ($n) => $n->where('name', 'like', $like)->orWhereHas('aliases', fn ($a) => $a->where('name', 'like', $like))));
            });
        }
    }

    public function companies($query, array $tokens): void
    {
        $match = app(IndustrialCareerMatcher::class)->match($tokens);
        if ($match['matches']) {
            $original = $tokens;
            $query->whereHas('jobs', function ($jobs) use ($original) { $jobs->live(); $this->jobs($jobs, $original); });
            $tokens = $match['remaining'];
        }
        foreach ($tokens as $token) {
            $like = '%'.$token.'%';
            $query->where(function ($q) use ($like, $token) {
                $q->where('name', 'like', $like)->orWhere('plant_name', 'like', $like)->orWhere('industry', 'like', $like)
                    ->orWhereHas('area', fn ($a) => $this->areaText($a, $like))
                    ->orWhereHas('jobs', function ($j) use ($token) {
                        $j->live();
                        $this->jobs($j, [$token]);
                    });
            });
        }
    }

    public function areas($query, array $tokens): void
    {
        $match = app(IndustrialCareerMatcher::class)->match($tokens);
        if ($match['matches']) {
            $original = $tokens;
            $query->whereHas('jobs', function ($jobs) use ($original) { $jobs->live(); $this->jobs($jobs, $original); });
            $tokens = $match['remaining'];
        }
        foreach ($tokens as $token) {
            $like = '%'.$token.'%';
            $query->where(function ($q) use ($like, $token) {
                $this->areaText($q, $like);
                $q->orWhereHas('companies', fn ($c) => $c->visible()->where(fn ($n) => $n->where('name', 'like', $like)->orWhere('plant_name', 'like', $like)))
                    ->orWhereHas('jobs', function ($j) use ($token) {
                        $j->live();
                        $this->jobs($j, [$token]);
                    });
            });
        }
    }

    private function areaText($query, string $like): void
    {
        $query->where(function ($q) use ($like) {
            $q->where('name', 'like', $like)->orWhere('city', 'like', $like)->orWhere('district', 'like', $like)
                ->orWhereHas('state', fn ($s) => $s->where('name', 'like', $like));
        });
    }
}
