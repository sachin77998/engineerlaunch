<?php
namespace App\Services;

use Carbon\Carbon;

class IndustrialCareerParser
{
    public function happyLinks(string $html): array
    {
        $dom = new \DOMDocument();@$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);$links=[];
        foreach ($xpath->query('//h3/a[@href]') as $anchor) {
            $url=$anchor->getAttribute('href');$title=trim($anchor->textContent);
            if ($title!=='' && preg_match('~^https://happyforgingsltd\.com/opportunities/[a-z0-9-]+/$~', $url)) $links[$url]=$title;
        }
        return $links;
    }

    public function sonalika(string $html): array
    {
        if (!preg_match('/const\s+allJobs\s*=\s*(\[.*?\]);/s', $html, $match)) {
            throw new \RuntimeException('Sonalika vacancy data was not found; existing jobs preserved.');
        }
        $rows = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        $jobs = [];
        foreach ($rows as $row) {
            if (empty($row['job_id']) || empty($row['job_title']) || empty($row['post_on_careers_page'])) continue;
            $jobs[] = [
                'title' => $row['job_title'],
                'location' => implode('; ', $row['location'] ?? $row['location_city'] ?? []),
                'country' => $row['location_country'] ?? 'India',
                'category' => $row['department'] ?? null,
                'description' => 'Department: '.($row['department'] ?? 'Not specified').'. Reference: '.($row['job_code'] ?? $row['job_id']).'. See the official employer page for complete requirements.',
                'experience_min' => is_numeric($row['experience_from'] ?? null) ? $row['experience_from'] : null,
                'experience_max' => is_numeric($row['experience_to'] ?? null) ? $row['experience_to'] : null,
                'job_type' => $row['employee_type'] ?? null,
                'posted_at' => !empty($row['job_created_timestamp']) ? Carbon::createFromFormat('d-m-Y H:i:s', $row['job_created_timestamp'], 'Asia/Kolkata') : null,
                'external_url' => 'https://sonalika.darwinbox.in/ms/candidate/careers/'.rawurlencode($row['job_id']),
                'posting_source' => 'official_company',
            ];
        }
        return $jobs;
    }

    // For smaller employers that publish standard JobPosting JSON-LD.
    // A generic CV form or a company address never becomes a vacancy.
    public function structured(string $html, string $sourceUrl): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);
        $jobs = [];
        $visit = function ($node) use (&$visit, &$jobs, $sourceUrl) {
            if (!is_array($node)) return;
            if (in_array('JobPosting', (array) ($node['@type'] ?? []), true)) {
                $url = $node['url'] ?? null;
                if (!$url || !filter_var($url, FILTER_VALIDATE_URL) || empty($node['title'])) return;
                $deadline = !empty($node['validThrough']) ? Carbon::parse($node['validThrough']) : null;
                if ($deadline && $deadline->isPast()) return;
                $places = $node['jobLocation'] ?? [];
                if (isset($places['address'])) $places = [$places];
                $locations = [];
                foreach ($places as $place) {
                    $address = $place['address'] ?? [];
                    if (!is_array($address)) continue;
                    $country = $address['addressCountry'] ?? '';
                    if (is_array($country)) $country = $country['name'] ?? '';
                    $locations[] = implode(', ', array_filter([$address['addressLocality'] ?? '', $address['addressRegion'] ?? '', $country]));
                }
                $jobs[$url] = ['title' => strip_tags($node['title']), 'description' => strip_tags($node['description'] ?? ''),
                    'external_url' => $url, 'location' => implode('; ', array_filter($locations)) ?: 'Not specified',
                    'posted_at' => $node['datePosted'] ?? null, 'expires_at' => $deadline,
                    'job_type' => implode(', ', (array) ($node['employmentType'] ?? [])), 'posting_source' => 'official_company'];
                return;
            }
            foreach ($node as $child) if (is_array($child)) $visit($child);
        };
        foreach ($xpath->query('//script[@type="application/ld+json"]') as $script) {
            $visit(json_decode($script->textContent, true, 512, JSON_THROW_ON_ERROR));
        }
        return array_values($jobs);
    }
}
