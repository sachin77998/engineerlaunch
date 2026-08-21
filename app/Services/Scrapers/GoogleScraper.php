<?php

namespace App\Services\Scrapers;

use App\Models\Company;
use App\Models\Technology;
use App\Models\JobCategory;
use App\Services\JobScraper;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Google Careers Scraper
 * Note: This is a template - Google's actual HTML structure may differ
 * and they may have API endpoints that are easier to use
 */
class GoogleScraper extends JobScraper
{
    protected function scrapeCompanyJobs(Company $company): array
    {
        $jobs = [];
        
        // Note: Google likely has an API endpoint like:
        // https://www.google.com/api/careers/search?location=India&team=Engineering
        
        // For now, this is a placeholder implementation
        // In production, you would need to:
        // 1. Analyze Google's careers page structure
        // 2. Use API endpoints if available
        // 3. Handle pagination
        // 4. Parse job details from their listing pages
        
        $baseUrl = 'https://careers.google.com/jobs';
        
        try {
            // This is simplified - actual implementation would need proper pagination
            $content = $this->getContent($baseUrl);
            $crawler = $this->parseHtml($content);
            
            // Parse job listings (structure may vary)
            $crawler->filter('[data-job-id]')->each(function (Crawler $node) use (&$jobs) {
                $jobData = [
                    'title' => $node->attr('data-job-title') ?? 'Software Engineer',
                    'location' => $node->attr('data-job-location') ?? 'Not specified',
                    'country' => $this->extractCountry($node->attr('data-job-location')),
                    'external_url' => $node->filter('a')->attr('href'),
                    'job_type' => 'Full-time',
                    'posted_at' => now(),
                    'technologies' => $this->extractTechnologies($node->attr('data-job-title')),
                    'categories' => $this->extractCategories($node->attr('data-job-title')),
                ];
                
                $jobs[] = $jobData;
            });
        } catch (\Exception $e) {
            // Log error but don't throw - allow other companies to be scraped
            \Log::warning("Google scraper failed: " . $e->getMessage());
        }
        
        return $jobs;
    }

    protected function extractCountry(?string $location): string
    {
        if (!$location) return 'India';
        
        $countryMap = [
            'India' => 'India',
            'United States' => 'USA',
            'USA' => 'USA',
            'UK' => 'UK',
            'Europe' => 'Europe',
            'Germany' => 'Germany',
            'France' => 'France',
            'Canada' => 'Canada',
            'Singapore' => 'Singapore',
        ];
        
        foreach ($countryMap as $search => $country) {
            if (stripos($location, $search) !== false) {
                return $country;
            }
        }
        
        return 'Other';
    }

    protected function extractTechnologies(?string $text): array
    {
        if (!$text) return [];
        
        $keywords = ['Java', 'Python', 'Go', 'C++', 'JavaScript', 'TypeScript', 'React', 'Angular', 'Vue', 'Spring', 'Django', 'FastAPI'];
        $found = [];
        
        foreach ($keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                $tech = Technology::where('name', $keyword)->first();
                if ($tech) {
                    $found[] = $tech->id;
                }
            }
        }
        
        return $found;
    }

    protected function extractCategories(?string $text): array
    {
        if (!$text) return [];
        
        $categoryKeywords = [
            'SDE' => 'Software Developer',
            'SDE 2' => 'Software Developer 2',
            'SDE 3' => 'Software Developer 3',
            'Staff' => 'Staff Engineer',
            'Product Manager' => 'Product Manager',
            'ML' => 'ML Engineer',
            'Data' => 'Data Engineer',
        ];
        
        $found = [];
        foreach ($categoryKeywords as $keyword => $category) {
            if (stripos($text, $keyword) !== false) {
                $cat = JobCategory::where('name', $category)->first();
                if ($cat) {
                    $found[] = $cat->id;
                }
            }
        }
        
        return $found;
    }
}
