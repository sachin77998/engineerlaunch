<?php

namespace App\Services;

use App\Models\IndustrialJobRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class IndustrialCareerMatcher
{
    private ?Collection $catalog = null;
    public function catalog(): Collection
    {
        if ($this->catalog !== null) {return $this->catalog;}
        return $this->catalog = $this->query()->with(['profile','aliases','processes' => function ($query) {$query->where('is_active', true);},
                'sectors' => function ($query) { $query->where('is_active', true);},
                'relatedRoles',
                'department',])
            ->get();
    }
    private function query(): Builder
    {
        return IndustrialJobRole::query()->where('is_active', true)->whereHas('department',fn($query) => $query->where('is_active', true));
    }
    private function normalize(string $text): string
    {
        return implode(' ',app(IndustrialSearch::class)->tokens($text));
    }
    public function match(array $tokens): array
    {
        $normalizedTokens = array_values(array_filter(array_map(fn($token) => $this->normalize((string) $token),$tokens),fn($token) => $token !== ''));
        $phrase = implode(' ', $normalizedTokens);
        if ($phrase === '') {
            return ['matches' => [],'remaining' => [],];
        }
        $matches = [];
        $best = '';
        foreach ($this->catalog() as $role) {
            $terms = [[$role->name,100,'Exact role title',],];
            foreach ($role->aliases as $alias) {
                $terms[] = [$alias->name,90,'Alias: ' . $alias->name,];
            }
            foreach ($role->profile?->skills ?? [] as $skill) {
                $terms[] = [$skill,60,'Skill: ' . $skill,];
            }
            foreach ($role->processes as $process) {
                $terms[] = [$process->name,55,'Process: ' . $process->name,];
            }
            foreach ($role->sectors as $sector) {
                $terms[] = [$sector->name,45,'Sector: ' . $sector->name,];
            }
            foreach ($role->profile?->qualifications ?? [] as $qualification) {
                $terms[] = [$qualification,35,'Background: ' . $qualification,];
            }
            foreach ($terms as [$term, $score, $reason]) {
                $normalizedTerm = $this->normalize((string) $term);
                if ($normalizedTerm === '') {continue;}
                if (!str_contains(' ' . $phrase . ' ', ' ' . $normalizedTerm . ' ')) {
                    continue;
                }
                if (strlen($normalizedTerm) > strlen($best)) {
                    $best = $normalizedTerm;
                    $matches = [];
                }
                if ($normalizedTerm === $best && ($matches[$role->id]['score'] ?? 0) < $score) {
                    $matches[$role->id] = ['score' => $score,'reason' => $reason,];
                }
            }
        }
        if ($best === '') {
            return [
                'matches' => [],
                'remaining' => $tokens,
            ];
        }

        $directMatches = $matches;

        foreach ($directMatches as $roleId => $match) {

            if ($match['score'] < 90) {
                continue;
            }

            $role = $this->catalog()->firstWhere(
                'id',
                $roleId
            );

            if (!$role) {
                continue;
            }

            foreach ($role->relatedRoles as $relatedRole) {

                if (
                    !$this->catalog()->contains(
                        'id',
                        $relatedRole->id
                    )
                ) {
                    continue;
                }

                $relatedScore = min(
                    $match['score'] - 1,
                    (int) $relatedRole->pivot->weight
                );

                if (
                    ($matches[$relatedRole->id]['score'] ?? 0)
                    < $relatedScore
                ) {
                    $matches[$relatedRole->id] = [
                        'score' => $relatedScore,
                        'reason' => $relatedRole->pivot->reason,
                    ];
                }
            }
        }

        
        uasort(
            $matches,
            fn($a, $b) => $b['score'] <=> $a['score']
        );

        
        $remainingPhrase = trim(
            preg_replace(
                '/(?<!\S)' .
                    preg_quote($best, '/') .
                    '(?!\S)/u',
                '',
                $phrase,
                1
            )
        );

        $remaining = $remainingPhrase === ''
            ? []
            : preg_split('/\s+/u',$remainingPhrase);

        return ['matches' => $matches,'remaining' => $remaining,];
    }
}
