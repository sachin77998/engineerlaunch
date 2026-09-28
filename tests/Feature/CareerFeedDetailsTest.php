<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Services\JobScraper;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class CareerFeedDetailsTest extends TestCase
{
    private function scraper(array $responses): JobScraper
    {
        return new class(new Client(['handler' => HandlerStack::create(new MockHandler($responses))])) extends JobScraper {
            public function __construct(Client $client) { $this->httpClient = $client; }
            public function workday(Company $company): array { return $this->scrapeWorkday($company); }
            public function lever(Company $company): array { return $this->scrapeLever($company); }
            public function smart(Company $company): array { return $this->scrapeSmartRecruiters($company); }
            public function complete(): bool { return $this->completeFeed; }
            protected function normalizeJob(Company $company, array $job): array { return $job; }
        };
    }

    public function test_workday_imports_skills_from_the_full_description(): void
    {
        $scraper = $this->scraper([
            new Response(200, [], json_encode(['total'=>1,'jobPostings'=>[
                ['title'=>'Software Engineer','externalPath'=>'/job/Gurugram/Engineer_R1','locationsText'=>'Gurugram','bulletFields'=>['R1']],
            ]])),
            new Response(200, [], json_encode(['jobPostingInfo'=>['jobDescription'=>'<p>Java, Spring Boot and Kafka experience.</p>']])),
        ]);
        $jobs = $scraper->workday(new Company(['country'=>'India','jobs_feed_url'=>'https://example.test/wday/cxs/company/Careers/jobs']));
        $this->assertStringContainsString('Spring Boot', $jobs[0]['description']);
        $this->assertTrue($jobs[0]['description_complete']);
        $this->assertTrue($scraper->complete());
    }

    public function test_failed_workday_details_do_not_allow_catalogue_retirement(): void
    {
        $scraper = $this->scraper([
            new Response(200, [], json_encode(['total'=>1,'jobPostings'=>[
                ['title'=>'Engineer','externalPath'=>'/job/Engineer_R1','bulletFields'=>['R1']],
            ]])),
            new Response(503),
        ]);
        $jobs = $scraper->workday(new Company(['country'=>'India','jobs_feed_url'=>'https://example.test/wday/cxs/company/Careers/jobs']));
        $this->assertFalse($jobs[0]['description_complete']);
        $this->assertFalse($scraper->complete());
    }

    public function test_lever_includes_requirements_lists(): void
    {
        $scraper = $this->scraper([new Response(200, [], json_encode([
            ['text'=>'Engineer','descriptionPlain'=>'Build services','hostedUrl'=>'https://jobs.lever.co/example/1',
                'lists'=>[['content'=>'<li>Python, Django and Docker</li>']]],
        ]))]);
        $jobs = $scraper->lever(new Company(['ats_identifier'=>'example','country'=>'Global']));
        $this->assertStringContainsString('Django', $jobs[0]['description']);
    }
    public function test_smartrecruiters_imports_description_and_qualifications(): void
    {
        $scraper = $this->scraper([
            new Response(200, [], json_encode(['totalFound'=>1,'content'=>[['id'=>'1','name'=>'Engineer','releasedDate'=>'2026-09-26']]])),
            new Response(200, [], json_encode(['jobAd'=>['sections'=>[
                'jobDescription'=>['text'=>'<p>Build PHP applications.</p>'],
                'qualifications'=>['text'=>'<p>Laravel experience required.</p>'],
            ]]])),
        ]);
        $jobs = $scraper->smart(new Company(['ats_identifier'=>'example','country'=>'India']));
        $this->assertStringContainsString('Laravel', $jobs[0]['description']);
        $this->assertStringContainsString('PHP', $jobs[0]['description']);
        $this->assertTrue($scraper->complete());
    }

}
