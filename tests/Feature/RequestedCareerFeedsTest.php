<?php
namespace Tests\Feature;

use App\Models\Company;
use App\Services\JobScraper;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class RequestedCareerFeedsTest extends TestCase
{
    private function scraper(array $responses): JobScraper
    {
        return new class(new Client(['handler'=>HandlerStack::create(new MockHandler($responses))])) extends JobScraper {
            public function __construct(Client $client) { $this->httpClient=$client; }
            public function fetch(Company $company): array { $rows=$this->scrapeCompanyJobs($company); return is_array($rows) ? $rows : iterator_to_array($rows); }
            public function complete(): bool { return $this->completeFeed; }
            protected function normalizeJob(Company $company,array $job): array { return $job; }
        };
    }
    public function test_new_successfactors_widget_imports_official_detail_links(): void
    {
        config(['cache.default'=>'array']);
        $scraper=$this->scraper([
            new Response(200,[],"<script>var CSRFToken='public-token';</script>xweb/rmk-jobs-search"),
            new Response(200,[],json_encode(['totalJobs'=>1,'jobSearchResult'=>[['response'=>[
                'id'=>'123','unifiedStandardTitle'=>'Java Developer','unifiedUrlTitle'=>'Java-Developer',
                'jobLocationShort'=>['Pune, India'],'unifiedStandardStart'=>'9/30/26',
            ]]]])),
            new Response(200,[],'<div itemprop="description">Java Spring Boot Docker</div>'),
        ]);
        $rows=$scraper->fetch(new Company(['ats_provider'=>'successfactors','careers_url'=>'https://careers.example.test/search/?q=']));
        $this->assertCount(1,$rows);
        $this->assertSame('https://careers.example.test/job/Java-Developer/123-en_US',$rows[0]['external_url']);
        $this->assertStringContainsString('Docker',$rows[0]['description']);
    }

    public function test_discovery_obeys_shared_robots_groups_and_specific_agent_rules(): void
    {
        $discovery=new class extends \App\Services\OfficialCareerDiscovery {
            public function permits(Client $client,string $url): bool { return $this->robotsAllow($client,$url); }
        };
        $client=new Client(['handler'=>HandlerStack::create(new MockHandler([
            new Response(200,[],"User-agent: *\nUser-agent: AnotherBot\nDisallow: /careers\nAllow: /careers/public\n"),
        ]))]);
        $this->assertFalse($discovery->permits($client,'https://example.test/careers/private'));
        $this->assertTrue($discovery->permits($client,'https://example.test/careers/public'));
    }

    public function test_bebo_imports_explicit_vacancies_and_original_apply_urls(): void
    {
        $html='<div class="India_Careers1_jobs"><div class="single-job"><h5>PHP Developer</h5><p class="job-subtitle">3 - 5 Years</p><div class="job-description-wrapper">Laravel and MySQL</div><button data-microsite="https://bebotechnologiesin.mobile-recruit.com/m/test"></button></div></div><div class="single-job"><h5>Join our team</h5></div>';
        $rows=$this->scraper([new Response(200,[],$html)])->fetch(new Company(['ats_provider'=>'bebo','careers_url'=>'https://www.bebotechnologies.com/careers']));
        $this->assertCount(1,$rows);
        $this->assertSame('Chandigarh, India',$rows[0]['location']);
        $this->assertSame(3,$rows[0]['experience_min']);
        $this->assertStringContainsString('Laravel',$rows[0]['description']);
    }
    public function test_infosys_uses_public_search_and_full_description(): void
    {
        $scraper=$this->scraper([
            new Response(200,[],'<script src="https://example.test/merged/js/test.js"></script>'),
            new Response(200,[],"const x=algoliasearch('PUBLICAPP','PUBLICSEARCHKEY');const y={indexName:'public_jobs'};"),
            new Response(200,[],json_encode(['nbHits'=>1,'nbPages'=>1,'hits'=>[
                ['title'=>'Developer','redirect_url'=>['https://digitalcareers.infosys.com/global-careers/company-job/description/reqid/123BR'],'work_location'=>['Paris'],'country'=>['France']],
            ]])),
            new Response(200,[],'<div class="description-page-right">Java Spring Boot and Kafka</div>'),
        ]);
        $rows=$scraper->fetch(new Company(['ats_provider'=>'infosys_algolia','careers_url'=>'https://digitalcareers.infosys.com/']));
        $this->assertSame('Paris, France',$rows[0]['location']);
        $this->assertStringContainsString('Kafka',$rows[0]['description']);
        $this->assertTrue($scraper->complete());
    }
    public function test_oracle_keeps_job_identity_dates_and_full_requirements(): void
    {
        $scraper=$this->scraper([
            new Response(200,[],json_encode(['items'=>[['TotalJobsCount'=>1,'requisitionList'=>[
                ['Id'=>'123','Title'=>'QA Engineer','PrimaryLocation'=>'India','PostedDate'=>'2026-09-29','ShortDescriptionStr'=>'QA'],
            ]]]])),
            new Response(200,[],json_encode(['items'=>[['ExternalDescriptionStr'=>'<p>Python automation</p>','PrimaryLocation'=>'Pune, India','ExternalQualificationsStr'=>'Playwright']]])),
        ]);
        $rows=$scraper->fetch(new Company(['ats_provider'=>'oracle_recruiting','ats_identifier'=>'CX_1','jobs_feed_url'=>'https://example.test/recruitingCEJobRequisitions','careers_url'=>'https://example.test/sites/CX_1']));
        $this->assertSame('https://example.test/sites/CX_1/job/123',$rows[0]['external_url']);
        $this->assertStringContainsString('Playwright',$rows[0]['description']);
        $this->assertTrue($scraper->complete());
    }
    public function test_successfactors_detail_failure_prevents_retirement(): void
    {
        $scraper=$this->scraper([
            new Response(200,[],'<table><tr><td><a class="jobTitle-link" href="/job/test/123">Developer</a></td><td class="jobLocation">Berlin</td></tr></table>'),
            new Response(200,[],'<html>No more results</html>'),
            new Response(503),
        ]);
        $rows=$scraper->fetch(new Company(['ats_provider'=>'successfactors','careers_url'=>'https://example.test/search/']));
        $this->assertCount(1,$rows);
        $this->assertFalse($scraper->complete());
        $this->assertFalse($rows[0]['description_complete']);
    }
}
