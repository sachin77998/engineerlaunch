<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\CareerSourceDetector;
use Tests\TestCase;

class CareerSourceDetectorTest extends TestCase
{
    private CareerSourceDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = app(CareerSourceDetector::class);
    }

    public function test_configured_provider_is_preserved(): void
    {
        $company = new Company([
            'name' => 'Configured Employer',
            'ats_provider' => 'greenhouse',
            'ats_identifier' => 'example',
            'jobs_feed_url' => 'https://boards-api.greenhouse.io/v1/boards/example/jobs',
        ]);

        $result = $this->detector->detect($company);

        $this->assertSame('greenhouse', $result['provider']);
        $this->assertSame('example', $result['identifier']);
        $this->assertSame('configured', $result['confidence']);
        $this->assertSame('database', $result['source']);
    }

    public function test_workday_source_is_detected(): void
    {
        $url = 'https://example.myworkdayjobs.com/wday/cxs/example/company/jobs';

        $result = $this->detector->detectFromUrl($url);

        $this->assertSame('workday', $result['provider']);
        $this->assertSame($url, $result['jobs_feed_url']);
    }

    public function test_oracle_recruiting_source_is_detected(): void
    {
        $url = 'https://example.fa.ocs.oraclecloud.com/hcmUI/CandidateExperience/en/sites/CX_1';

        $result = $this->detector->detectFromUrl($url);

        $this->assertSame('oracle_recruiting', $result['provider']);
        $this->assertSame('CX_1', $result['identifier']);
    }

    public function test_amazon_source_is_detected(): void
    {
        $result = $this->detector->detectFromUrl(
            'https://www.amazon.jobs/en/search'
        );

        $this->assertSame('amazon', $result['provider']);
        $this->assertSame(
            'https://www.amazon.jobs/en/search.json',
            $result['jobs_feed_url']
        );
    }

    public function test_http_sources_are_not_accepted(): void
    {
        $result = $this->detector->detectFromUrl(
            'http://example.myworkdayjobs.com/wday/cxs/example/company/jobs'
        );

        $this->assertNull($result['provider']);
        $this->assertSame('unknown', $result['confidence']);
    }

    public function test_unknown_source_returns_unknown(): void
    {
        $result = $this->detector->detectFromUrl(
            'https://example-company.com/careers'
        );

        $this->assertNull($result['provider']);
        $this->assertNull($result['identifier']);
        $this->assertNull($result['jobs_feed_url']);
        $this->assertSame('unknown', $result['confidence']);
    }

    public function test_company_detection_checks_jobs_feed_first(): void
    {
        $company = new Company([
            'name' => 'Test Employer',
            'ats_provider' => 'official_discovery',
            'jobs_feed_url' => 'https://www.amazon.jobs/en/search.json',
            'careers_url' => 'https://example-company.com/careers',
            'website' => 'https://example-company.com',
        ]);

        $result = $this->detector->detect($company);

        $this->assertSame('amazon', $result['provider']);
        $this->assertSame(
            'https://www.amazon.jobs/en/search.json',
            $result['jobs_feed_url']
        );
    }
}
