<?php

namespace App\Services;

use App\Models\Company;

class JobClassificationService
{
    public const VERSION = 1;

    public function classify(array $job, ?Company $company = null): array
    {
        $text = mb_strtolower(implode(' ', array_filter([
            $job['title'] ?? null, $job['role'] ?? null, $job['category'] ?? null,
            $job['description'] ?? null, $this->flatten($job['requirements'] ?? null),
            $this->flatten($job['responsibilities'] ?? null), $company?->industry, $company?->sector,
        ])));

        return [
            'department' => $this->department($text),
            'engineering_discipline' => $this->discipline($text),
            'classification_version' => self::VERSION,
            'classified_at' => now(),
        ];
    }

    private function department(string $text): string
    {
        $rules = [
            'Quality & Testing' => ['quality assurance', 'qa engineer', 'sdet', 'software test', 'test automation', 'manual test', 'performance test'],
            'Human Resources' => ['human resources', 'talent acquisition', 'recruiter', 'people operations', 'hr business'],
            'Administration & Facilities' => ['administrative', 'administration', 'office manager', 'facilities', 'front desk'],
            'Data Science & Analytics' => ['data scientist', 'data analyst', 'machine learning', 'artificial intelligence', 'business intelligence', 'analytics engineer'],
            'IT & Information Security' => ['cybersecurity', 'information security', 'security engineer', 'soc analyst', 'penetration test', 'iam engineer'],
            'Cloud, DevOps & Infrastructure' => ['devops', 'site reliability', 'platform engineer', 'cloud engineer', 'kubernetes', 'infrastructure engineer'],
            'Engineering - Hardware & Networks' => ['network engineer', 'hardware engineer', 'embedded engineer', 'firmware', 'semiconductor', 'vlsi'],
            'Engineering - Mechanical' => ['mechanical engineer', 'cad engineer', 'thermal engineer', 'automotive engineer', 'manufacturing engineer'],
            'Engineering - Chemical & Process' => ['chemical engineer', 'process engineer', 'petrochemical', 'polymer engineer'],
            'Engineering - Civil & Construction' => ['civil engineer', 'structural engineer', 'construction engineer', 'site engineer', 'quantity surveyor'],
            'Engineering - Electrical & Electronics' => ['electrical engineer', 'electronics engineer', 'power systems', 'control systems', 'instrumentation engineer'],
            'Product Management' => ['product manager', 'product owner', 'product management'],
            'UX, Design & Architecture' => ['ux designer', 'ui designer', 'product designer', 'graphic designer', 'solution architect', 'software architect'],
            'Finance & Accounting' => ['accountant', 'finance analyst', 'financial analyst', 'taxation', 'audit manager', 'controller'],
            'Sales & Business Development' => ['sales manager', 'account executive', 'business development', 'sales engineer', 'pre-sales'],
            'Marketing & Communication' => ['marketing', 'content writer', 'communications', 'seo specialist', 'brand manager'],
            'Customer Success, Service & Operations' => ['customer success', 'customer support', 'service operations', 'technical support', 'operations executive'],
            'Procurement & Supply Chain' => ['procurement', 'supply chain', 'logistics', 'warehouse', 'vendor manager'],
            'Project & Program Management' => ['project manager', 'program manager', 'scrum master', 'delivery manager'],
            'Research & Development' => ['research scientist', 'research engineer', 'r&d engineer'],
            'Consulting' => ['consultant', 'consulting', 'advisory'],
            'Engineering - Software' => ['software', 'developer', 'programmer', 'frontend', 'front-end', 'backend', 'back-end', 'full stack', 'fullstack', 'web engineer', 'mobile app', 'java', 'php', 'python', 'react', 'node.js'],
        ];

        foreach ($rules as $label => $terms) if ($this->contains($text, $terms)) return $label;
        return 'Other';
    }

    private function discipline(string $text): string
    {
        $rules = [
            'Mechanical Engineering' => ['mechanical', 'automotive', 'thermal', 'cad engineer', 'machinery', 'manufacturing engineer'],
            'Chemical & Process Engineering' => ['chemical', 'petrochemical', 'polymer', 'process engineer', 'refinery'],
            'Civil & Construction Engineering' => ['civil engineer', 'structural', 'construction', 'site engineer', 'quantity surveyor', 'real estate'],
            'Electrical & Electronics Engineering' => ['electrical', 'electronics', 'semiconductor', 'vlsi', 'embedded', 'firmware', 'instrumentation', 'power systems'],
            'Computer Science & Software Engineering' => ['software', 'developer', 'programmer', 'web', 'data scientist', 'machine learning', 'artificial intelligence', 'devops', 'cloud', 'cybersecurity', 'quality assurance', 'sdet', 'java', 'php', 'python', 'react', 'node.js'],
        ];
        foreach ($rules as $label => $terms) if ($this->contains($text, $terms)) return $label;
        return 'General / Interdisciplinary';
    }

    private function contains(string $text, array $terms): bool
    {
        foreach ($terms as $term) if (str_contains($text, $term)) return true;
        return false;
    }

    private function flatten(mixed $value): ?string
    {
        if (is_array($value)) return implode(' ', array_map(fn ($item) => is_scalar($item) ? (string) $item : '', $value));
        return is_scalar($value) ? (string) $value : null;
    }
}
