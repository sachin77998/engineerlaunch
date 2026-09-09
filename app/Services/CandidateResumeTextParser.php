<?php

namespace App\Services;

use Carbon\Carbon;

class CandidateResumeTextParser
{
    public function parse(string $text, array $knownSkills = []): array
    {
        $lines = collect(preg_split('/\R/u', $text))
            ->map(fn ($line) => trim(preg_replace('/\s+/u', ' ', $line)))
            ->filter()
            ->values();

        preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text, $email);
        preg_match('/(?:\+?\d{1,3}[\s.-]?)?(?:\(?\d{2,4}\)?[\s.-]?)?\d{6,10}/', $text, $phone);

        $name = $lines->first(fn ($line) => $this->looksLikeName($line));
        $location = $lines->first(fn ($line) => preg_match('/^[A-Za-z][A-Za-z .-]+,\s*(?:India|[A-Za-z ]+)(?:\s+\d{5,6})?$/i', $line));
        $location = $location ? trim(preg_replace('/\s+\d{5,6}$/', '', $location)) : null;

        [$company, $designation] = $this->employment($lines->all(), $knownSkills);
        $institution = $lines->first(fn ($line) =>
            preg_match('/\b(university|college|institute|school|academy)\b/i', $line)
            && !preg_match('/^(education|experience|skills|links|websites|projects)\b/i', $line)
            && mb_strlen($line) <= 150
        );
        $institution = $institution ? trim(explode('•', $institution, 2)[0]) : null;

        $skills = collect($knownSkills)
            ->filter(fn ($skill) => preg_match('/(?<![A-Za-z0-9])'.preg_quote($skill, '/').'(?![A-Za-z0-9])/i', $text))
            ->values()->take(40)->all();

        $experience = $this->experienceYears($text);
        $parts = $name ? preg_split('/\s+/', $name, 2) : [];

        return [
            'name' => $name,
            'first_name' => $parts[0] ?? null,
            'last_name' => $parts[1] ?? null,
            'email' => $email[0] ?? null,
            'phone' => isset($phone[0]) ? trim($phone[0]) : null,
            'location' => $location,
            'address' => $location,
            'headline' => $designation,
            'current_company' => $company,
            'designation' => $designation,
            'preferred_role' => $designation,
            'experience' => $experience,
            'education' => $institution,
            'skills' => $skills,
            'companies' => $company ? [$company] : [],
        ];
    }

    private function looksLikeName(string $line): bool
    {
        return (bool) preg_match('/^[A-Za-z][A-Za-z .\'-]{2,79}$/', $line)
            && count(preg_split('/\s+/', $line)) <= 5
            && !preg_match('/^(skills|experience|education|projects|links|profile|resume|curriculum vitae)$/i', $line);
    }

    private function employment(array $lines, array $knownSkills): array
    {
        foreach ($lines as $line) {
            if (preg_match('/^(.{2,100}?)\s+-\s+(.{2,100}\b(?:developer|engineer|analyst|architect|manager|consultant|designer|tester|intern)\b.*)$/i', $line, $match)) {
                $company = trim($match[1], " \t•");
                foreach (collect($knownSkills)->sortByDesc(fn ($skill) => mb_strlen($skill)) as $skill) {
                    $company = preg_replace('/(?<![A-Za-z0-9])'.preg_quote($skill, '/').'(?![A-Za-z0-9])/i', '', $company);
                }
                $company = trim(preg_replace('/\s{2,}/', ' ', $company), " \t•-");
                $company = trim(preg_replace('/^(?:and\b\s*)+/i', '', $company));

                return [$company, trim($match[2], " \t•")];
            }
        }

        return [null, null];
    }

    private function experienceYears(string $text): ?float
    {
        if (preg_match('/(\d{1,2})\/(\d{4})\s*-\s*(current|present|\d{1,2}\/\d{4})/i', $text, $match)) {
            $start = Carbon::create((int) $match[2], (int) $match[1], 1);
            $end = in_array(strtolower($match[3]), ['current', 'present'], true)
                ? now()
                : Carbon::createFromFormat('m/Y', $match[3])->startOfMonth();

            return round($start->floatDiffInYears($end), 1);
        }

        if (preg_match('/(\d{1,2}(?:\.\d+)?)\s*\+?\s*(?:years?|yrs?)/i', $text, $match)) {
            return (float) $match[1];
        }

        return null;
    }
}
