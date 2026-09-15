<?php

namespace App\Services;

use App\Models\IndustrialJobRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class IndustrialCareerMatcher
{
    /**
     * Cached active role catalog.
     */
    private ?Collection $catalog = null;

    /**
     * Get the complete active career-role catalog.
     *
     * Role
     * ├── Profile
     * ├── Aliases
     * ├── Processes
     * ├── Sectors
     * ├── Related Roles
     * └── Department
     */
    public function catalog(): Collection
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        return $this->catalog = $this->query()
            ->with([
                'profile',
                'aliases',

                'processes' => function ($query) {
                    $query->where('is_active', true);
                },

                'sectors' => function ($query) {
                    $query->where('is_active', true);
                },

                'relatedRoles',

                'department',
            ])
            ->get();
    }

    /**
     * Base query for active industrial job roles.
     */
    private function query(): Builder
    {
        return IndustrialJobRole::query()
            ->where('is_active', true)
            ->whereHas(
                'department',
                fn($query) => $query->where('is_active', true)
            );
    }

    /**
     * Normalize a search term using the existing IndustrialSearch service.
     */
    private function normalize(string $text): string
    {
        return implode(
            ' ',
            app(IndustrialSearch::class)->tokens($text)
        );
    }

    /**
     * Match search tokens against the industrial career dictionary.
     *
     * Matching priority:
     *
     * Exact role title     = 100
     * Alias                 = 90
     * Skill                 = 60
     * Process               = 55
     * Sector                = 45
     * Qualification         = 35
     */
    public function match(array $tokens): array
    {
        $normalizedTokens = array_values(
            array_filter(
                array_map(
                    fn($token) => $this->normalize((string) $token),
                    $tokens
                ),
                fn($token) => $token !== ''
            )
        );

        $phrase = implode(' ', $normalizedTokens);

        if ($phrase === '') {
            return [
                'matches' => [],
                'remaining' => [],
            ];
        }

        $matches = [];
        $best = '';

        foreach ($this->catalog() as $role) {

            /*
            |--------------------------------------------------------------------------
            | ROLE TITLE
            |--------------------------------------------------------------------------
            */
            $terms = [
                [
                    $role->name,
                    100,
                    'Exact role title',
                ],
            ];

            /*
            |--------------------------------------------------------------------------
            | ROLE ALIASES
            |--------------------------------------------------------------------------
            */
            foreach ($role->aliases as $alias) {
                $terms[] = [
                    $alias->name,
                    90,
                    'Alias: ' . $alias->name,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | ROLE PROFILE SKILLS
            |--------------------------------------------------------------------------
            */
            foreach ($role->profile?->skills ?? [] as $skill) {
                $terms[] = [
                    $skill,
                    60,
                    'Skill: ' . $skill,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | PROCESSES
            |--------------------------------------------------------------------------
            */
            foreach ($role->processes as $process) {
                $terms[] = [
                    $process->name,
                    55,
                    'Process: ' . $process->name,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | SECTORS
            |--------------------------------------------------------------------------
            */
            foreach ($role->sectors as $sector) {
                $terms[] = [
                    $sector->name,
                    45,
                    'Sector: ' . $sector->name,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | QUALIFICATIONS
            |--------------------------------------------------------------------------
            */
            foreach ($role->profile?->qualifications ?? [] as $qualification) {
                $terms[] = [
                    $qualification,
                    35,
                    'Background: ' . $qualification,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | FIND BEST DICTIONARY TERM
            |--------------------------------------------------------------------------
            */
            foreach ($terms as [$term, $score, $reason]) {

                $normalizedTerm = $this->normalize(
                    (string) $term
                );

                if ($normalizedTerm === '') {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Whole-term matching
                |--------------------------------------------------------------------------
                |
                | Prevents matching "cnc" inside unrelated words.
                |
                */
                if (
                    !str_contains(
                        ' ' . $phrase . ' ',
                        ' ' . $normalizedTerm . ' '
                    )
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Prefer the longest matching term.
                |--------------------------------------------------------------------------
                */
                if (strlen($normalizedTerm) > strlen($best)) {
                    $best = $normalizedTerm;
                    $matches = [];
                }

                if (
                    $normalizedTerm === $best
                    && ($matches[$role->id]['score'] ?? 0) < $score
                ) {
                    $matches[$role->id] = [
                        'score' => $score,
                        'reason' => $reason,
                    ];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Nothing matched
        |--------------------------------------------------------------------------
        */
        if ($best === '') {
            return [
                'matches' => [],
                'remaining' => $tokens,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | RELATED ROLES
        |--------------------------------------------------------------------------
        |
        | If an exact role or alias matched strongly, include related roles.
        |
        */
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

        /*
        |--------------------------------------------------------------------------
        | SORT BEST MATCHES FIRST
        |--------------------------------------------------------------------------
        */
        uasort(
            $matches,
            fn($a, $b) => $b['score'] <=> $a['score']
        );

        /*
        |--------------------------------------------------------------------------
        | REMOVE MATCHED TERM FROM SEARCH
        |--------------------------------------------------------------------------
        */
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
