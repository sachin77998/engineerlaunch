<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Services\OfficialCareerDiscovery;
use Tests\TestCase;

class OfficialCareerDiscoveryTest extends TestCase
{
    private OfficialCareerDiscovery $discovery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->discovery = app(OfficialCareerDiscovery::class);
    }

    public function test_workday_board_is_detected(): void
    {
        $result = $this->discovery->board(
            'https://acme.wd1.myworkdayjobs.com/en-US/acme',
            ''
        );

        $this->assertIsArray($result);

        $this->assertSame(
            'workday',
            $result['ats_provider']
        );

        $this->assertSame(
            'https://acme.wd1.myworkdayjobs.com/acme',
            $result['careers_url']
        );

        $this->assertSame(
            'https://acme.wd1.myworkdayjobs.com/wday/cxs/acme/acme/jobs',
            $result['jobs_feed_url']
        );
    }

    public function test_oracle_recruiting_board_is_detected(): void
    {
        $result = $this->discovery->board(
            'https://example.fa.oraclecloud.com/hcmUI/CandidateExperience/en/sites/CX_1',
            ''
        );

        $this->assertIsArray($result);

        $this->assertSame(
            'oracle_recruiting',
            $result['ats_provider']
        );

        $this->assertSame(
            'CX_1',
            $result['ats_identifier']
        );

        $this->assertSame(
            'https://example.fa.oraclecloud.com/hcmRestApi/resources/latest/recruitingCEJobRequisitions',
            $result['jobs_feed_url']
        );
    }

    public function test_greenhouse_board_is_detected(): void
    {
        $result = $this->discovery->board(
            'https://boards.greenhouse.io/acme',
            ''
        );

        $this->assertIsArray($result);

        $this->assertSame(
            'greenhouse',
            $result['ats_provider']
        );

        $this->assertSame(
            'acme',
            $result['ats_identifier']
        );

        $this->assertSame(
            'https://boards.greenhouse.io/acme',
            $result['careers_url']
        );
    }

    public function test_lever_board_is_detected(): void
    {
        $result = $this->discovery->board(
            'https://jobs.lever.co/acme',
            ''
        );

        $this->assertIsArray($result);

        $this->assertSame(
            'lever',
            $result['ats_provider']
        );

        $this->assertSame(
            'acme',
            $result['ats_identifier']
        );

        $this->assertSame(
            'https://jobs.lever.co/acme',
            $result['careers_url']
        );
    }

    public function test_smartrecruiters_board_is_detected(): void
    {
        $result = $this->discovery->board(
            'https://jobs.smartrecruiters.com/acme',
            ''
        );

        $this->assertIsArray($result);

        $this->assertSame(
            'smartrecruiters',
            $result['ats_provider']
        );

        $this->assertSame(
            'acme',
            $result['ats_identifier']
        );

        $this->assertSame(
            'https://jobs.smartrecruiters.com/acme',
            $result['careers_url']
        );
    }

    public function test_successfactors_board_is_detected_from_html(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Careers</title>
</head>
<body>
    <div class="jobTitle-link">
        Software Engineer
    </div>
</body>
</html>
HTML;

        $result = $this->discovery->board(
            'https://careers.example.com/careers',
            $html
        );

        $this->assertIsArray($result);
        $this->assertSame('successfactors',$result['ats_provider']);
        $this->assertSame('https://careers.example.com/search/?q=',$result['jobs_feed_url']);
        $this->assertSame('https://careers.example.com/',$result['careers_url']);
    }
    public function test_unknown_board_returns_null(): void
    {
        $result = $this->discovery->board('https://example.com/careers','<html><body><h1>Careers</h1></body></html>');
        $this->assertNull($result);
    }
    public function test_http_urls_are_not_allowed(): void
    {
        $company = new Company(['website' => 'https://example.com','careers_url' => 'https://example.com/careers',]);
        $this->assertFalse($this->discovery->allowed('http://example.com/careers',$company));
    }
    public function test_external_unrelated_domain_is_not_allowed(): void
    {
        $company = new Company(['website' => 'https://example.com','careers_url' => 'https://example.com/careers',]);
        $this->assertFalse($this->discovery->allowed('https://unrelated-example.com/jobs',$company));
    }
    public function test_company_domain_is_allowed(): void
    {
        $company = new Company(['website' => 'https://example.com','careers_url' => 'https://example.com/careers',]);
        $this->assertTrue($this->discovery->allowed('https://example.com/careers/software-engineer',$company));
    }
}
