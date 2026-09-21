<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Company, Job, IndustrialState, IndustrialArea, IndustrialCompany, IndustrialJob};
use App\Services\{IndustrialJobSourceService, IndustrialCareerParser};
use Illuminate\Support\Facades\DB;

class IndustrialRecruitmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_09_11_000001_create_industrial_directory.php'))->up();
        (require database_path('migrations/2026_09_12_000001_create_industrial_taxonomy.php'))->up();
    }
    private function plant(string $city): IndustrialCompany
    {
        $state=IndustrialState::firstOrCreate(['slug'=>'punjab'],['name'=>'Punjab','is_active'=>true]);
        $area=IndustrialArea::create(['state_id'=>$state->id,'name'=>$city.' cluster','slug'=>strtolower($city).'-'.IndustrialArea::count(),'city'=>$city,'is_active'=>true]);
        return IndustrialCompany::create(['industrial_area_id'=>$area->id,'name'=>'Example Motors','slug'=>'example-motors','is_active'=>true,'is_verified'=>true,'verification_status'=>'company_source','source_url'=>'https://example.org/plants','last_verified_at'=>now()]);
    }
    private function job(string $location): Job
    {
        $job=new Job(['title'=>'VMC Operator','location'=>$location,'status'=>'published','job_visibility'=>'public','is_active'=>true,'external_url'=>'https://example.org/jobs/123','posting_source'=>'official_company']);
        $job->setRelation('company',new Company(['name'=>'Example Motors Limited']));
        return $job;
    }
    public function test_company_identity_requires_city_and_rejects_ambiguous_plants(): void
    {
        $plant=$this->plant('Hoshiarpur'); $this->plant('Ludhiana');
        foreach (['Dallas, Texas','Punjab','Not specified','Hoshiarpura'] as $location) {
            $this->assertNull((new IndustrialJobSourceService)->ingest($this->job($location)));
        }
        $imported=(new IndustrialJobSourceService)->ingest($this->job('Hoshiarpur, Punjab, India'));
        $this->assertSame($plant->id,$imported->industrial_company_id);
        (new IndustrialJobSourceService)->ingest($this->job('Hoshiarpur, Punjab, India'));
        $this->assertSame(1,IndustrialJob::count());
        $this->plant('Hoshiarpur');
        $this->assertNull((new IndustrialJobSourceService)->ingest($this->job('Hoshiarpur, Punjab, India')));
        $this->assertFalse(IndustrialJob::first()->is_active);
    }
    public function test_inactive_or_expired_jobs_retire_industrial_copy(): void
    {
        $this->plant('Hoshiarpur');$service=new IndustrialJobSourceService;$job=$this->job('Hoshiarpur');
        $service->ingest($job);$job->expires_at=now()->subDay();
        $this->assertNull($service->ingest($job));$this->assertFalse(IndustrialJob::first()->is_active);
        $job->expires_at=null;$job->status='draft';$this->assertNull($service->ingest($job));
    }
    public function test_sonalika_parser_preserves_location_experience_and_identity(): void
    {
        $row=['job_id'=>'abc123','job_title'=>'Technician','post_on_careers_page'=>1,'location'=>['Hoshiarpur, Punjab, India'],'department'=>'Service','experience_from'=>'2','experience_to'=>'4'];
        $hidden=$row;$hidden['post_on_careers_page']=0;
        $jobs=(new IndustrialCareerParser)->sonalika('<script>const allJobs = '.json_encode([$row,$hidden]).';</script>');
        $this->assertCount(1,$jobs);$this->assertSame('Hoshiarpur, Punjab, India',$jobs[0]['location']);
        $this->assertSame('2',$jobs[0]['experience_min']);$this->assertStringEndsWith('/abc123',$jobs[0]['external_url']);
    }
    public function test_structured_parser_does_not_turn_company_or_expired_listing_into_job(): void
    {
        $parser=new IndustrialCareerParser;
        $this->assertSame([],$parser->structured('<h1>Send your CV</h1>','https://example.org/careers'));
        $data=['@graph'=>[['@type'=>'Organization','name'=>'Factory'],['@type'=>'JobPosting','title'=>'Expired','url'=>'https://example.org/old','validThrough'=>'2000-01-01'],['@type'=>'JobPosting','title'=>'Fitter','url'=>'https://example.org/new']]];
        $jobs=$parser->structured('<script type="application/ld+json">'.json_encode($data).'</script>','https://example.org/careers');
        $this->assertCount(1,$jobs);$this->assertSame('Not specified',$jobs[0]['location']);
    }
    public function test_successfactors_pagination_uses_the_actual_page_size(): void
    {
        $scraper=new class extends \App\Services\JobScraper {
            public array $offsets=[];
            protected function getContent(string $url): string {
                parse_str(parse_url($url,PHP_URL_QUERY),$q);$start=(int)$q['startrow'];$this->offsets[]=$start;
                $html='<table>';foreach(array_slice(range(1,7),$start,3) as $id) $html.='<tr><td><a class="jobTitle-link" href="/job/'.$id.'">Fitter '.$id.'</a></td><td class="jobLocation">Pantnagar, IN</td></tr>';
                return $html.'</table>';
            }
            protected function normalizeJob(Company $company,array $job): array {return $job;}
            public function fetch(): array {return $this->scrapeCompanyJobs(new Company(['ats_provider'=>'successfactors','careers_url'=>'https://example.org/search/']));}
        };
        $this->assertCount(7,$scraper->fetch());$this->assertSame([0,3,6,7],$scraper->offsets);
    }

    public function test_happy_forgings_uses_only_vacancy_title_links(): void
    {
        $html='<footer>Ludhiana</footer><h3><a href="https://happyforgingsltd.com/opportunities/fitter/">Fitter</a></h3><h3><a href="https://example.org/job">External</a></h3><a href="https://happyforgingsltd.com/opportunities/fitter/">Apply Now</a>';
        $this->assertSame(['https://happyforgingsltd.com/opportunities/fitter/'=>'Fitter'],(new IndustrialCareerParser)->happyLinks($html));
    }

    public function test_happy_importer_does_not_use_footer_as_job_location(): void
    {
        $scraper=new class extends \App\Services\JobScraper {
            protected function getContent(string $url): string {
                return str_ends_with($url,'/fitter/') ? '<h2>Job Description:</h2><p>Machine maintenance. 3-10 years of experience</p><h2>Apply Now</h2><footer>Ludhiana</footer>' : '<h3><a href="https://happyforgingsltd.com/opportunities/fitter/">Fitter</a></h3>';
            }
            public function fetch(): array {return $this->scrapeCompanyJobs(new Company(['ats_provider'=>'happy_forgings','careers_url'=>'https://happyforgingsltd.com/opportunities/']));}
        };
        $jobs=$scraper->fetch();$this->assertCount(1,$jobs);$this->assertSame('Not specified',$jobs[0]['location']);$this->assertSame('3',$jobs[0]['experience_min']);
    }

}
