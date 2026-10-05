<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\CareerSourceDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobScraperSourceDetectionTest extends TestCase
{
    use RefreshDatabase;

    private function createCompany(array $attributes): Company
    {
        return Company::create(array_merge([
            'name' => 'Test Company',
            'slug' => 'test-company-' . uniqid(),
            'website' => 'https://example.com',
            'careers_url' => 'https://example.com/careers',
            'country' => 'India',
            'is_active' => true,
            'sync_enabled' => true,
        ], $attributes));
    }

    public function test_explicit_provider_is_preserved(): void
    {
        $company = $this->createCompany([
            'name' => 'Configured Greenhouse Company',
            'slug' => 'configured-greenhouse-company',
            'website' => 'https://example.com',
            'careers_url' => 'https://boards.greenhouse.io/example',
            'ats_provider' => 'greenhouse',
            'ats_identifier' => 'example',
            'jobs_feed_url' => 'https://boards-api.greenhouse.io/v1/boards/example/jobs?content=true',
        ]);

        $this->assertSame('greenhouse', $company->ats_provider);
        $this->assertSame('example', $company->ats_identifier);
        $this->assertSame(
            'https://boards-api.greenhouse.io/v1/boards/example/jobs?content=true',
            $company->jobs_feed_url
        );
    }

    public function test_company_can_be_configured_for_automatic_source_detection(): void
    {
        $company = $this->createCompany([
            'name' => 'Automatic Discovery Company',
            'slug' => 'automatic-discovery-company',
            'website' => 'https://example.com',
            'careers_url' => 'https://example.com/careers',
            'ats_provider' => 'official_discovery',
            'ats_identifier' => null,
            'jobs_feed_url' => null,
        ]);

        $this->assertSame(
            'official_discovery',
            $company->ats_provider
        );

        $this->assertTrue(
            (bool) $company->sync_enabled
        );

        $this->assertNull(
            $company->ats_identifier
        );

        $this->assertNull(
            $company->jobs_feed_url
        );
    }

    public function test_career_source_detector_detects_workday_for_company(): void
    {
        $company = $this->createCompany([
            'name' => 'Workday Example',
            'slug' => 'workday-example',
            'website' => 'https://example.com',
            'careers_url' => 'https://acme.wd1.myworkdayjobs.com/acme',
            'ats_provider' => 'official_discovery',
            'ats_identifier' => null,
            'jobs_feed_url' => null,
        ]);

        $detector = app(CareerSourceDetector::class);

        $result = $detector->detect($company);

        $this->assertSame(
            'workday',
            $result['provider']
        );

        $this->assertSame(
            'https://acme.wd1.myworkdayjobs.com/wday/cxs/acme/acme/jobs',
            $result['jobs_feed_url']
        );
    }

    public function test_career_source_detector_detects_oracle_recruiting_for_company(): void
    {
        $company = $this->createCompany([
            'name' => 'Oracle Example',
            'slug' => 'oracle-example',
            'website' => 'https://example.com',
            'careers_url' => 'https://example.fa.oraclecloud.com/hcmUI/CandidateExperience/en/sites/CX_1',
            'ats_provider' => 'official_discovery',
            'ats_identifier' => null,
            'jobs_feed_url' => null,
        ]);

        $detector = app(CareerSourceDetector::class);

        $result = $detector->detect($company);

        $this->assertSame(
            'oracle_recruiting',
            $result['provider']
        );

        $this->assertSame(
            'CX_1',
            $result['identifier']
        );

        $this->assertSame(
            'https://example.fa.oraclecloud.com/hcmRestApi/resources/latest/recruitingCEJobRequisitions',
            $result['jobs_feed_url']
        );
    }

    public function test_career_source_detector_detects_amazon_for_company(): void
    {
        $company = $this->createCompany([
            'name' => 'Amazon Example',
            'slug' => 'amazon-example',
            'website' => 'https://www.amazon.jobs',
            'careers_url' => 'https://www.amazon.jobs/en/',
            'ats_provider' => 'official_discovery',
            'ats_identifier' => null,
            'jobs_feed_url' => null,
        ]);

        $detector = app(CareerSourceDetector::class);

        $result = $detector->detect($company);

        $this->assertSame(
            'amazon',
            $result['provider']
        );

        $this->assertSame(
            'https://www.amazon.jobs/en/search.json',
            $result['jobs_feed_url']
        );
    }

    public function test_unknown_company_remains_official_discovery(): void
    {
        $company = $this->createCompany([
            'name' => 'Unknown Recruitment Platform',
            'slug' => 'unknown-recruitment-platform',
            'website' => 'https://example.com',
            'careers_url' => 'https://example.com/careers',
            'ats_provider' => 'official_discovery',
            'ats_identifier' => null,
            'jobs_feed_url' => null,
        ]);

        $detector = app(CareerSourceDetector::class);

        $result = $detector->detect($company);

        $this->assertNull(
            $result['provider']
        );

        $this->assertNull(
            $result['identifier']
        );

        $this->assertNull(
            $result['jobs_feed_url']
        );

        $this->assertSame(
            'unknown',
            $result['confidence']
        );
    }
}
