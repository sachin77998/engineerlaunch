<?php
namespace Tests\Feature;

use App\Models\Job;
use App\Services\JobGeography;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class JobGeographyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);
        DB::purge('sqlite');
        Schema::create('jobs',function(Blueprint $t){$t->id();$t->softDeletes();$t->string('location');$t->string('country');$t->boolean('is_active')->default(true);$t->string('status')->default('published');$t->string('job_visibility')->default('public');$t->timestamp('expires_at')->nullable();});
        DB::table('jobs')->insert([
            ['id'=>1,'location'=>'Bengaluru','country'=>'Global'],
            ['id'=>2,'location'=>'Vancouver, British Columbia, Canada','country'=>'Global'],
            ['id'=>3,'location'=>'Dallas, Texas, United States','country'=>'Global'],
            ['id'=>4,'location'=>'San Francisco, California, United States','country'=>'USA'],
            ['id'=>5,'location'=>'Dubai, United Arab Emirates','country'=>'Global'],
            ['id'=>6,'location'=>'Pune, India','country'=>'Global'],
            ['id'=>7,'location'=>'Berlin, Germany','country'=>'Global'],
            ['id'=>8,'location'=>'Remote','country'=>'Global'],
            ['id'=>9,'location'=>'Tullamarine, Victoria','country'=>'Global'],
            ['id'=>10,'location'=>'New Delhi, India','country'=>'Global'],
        ]);
    }
    private function ids(array $filters): array
    {
        $q=Job::active(); app(JobGeography::class)->apply($q,$filters);return $q->orderBy('id')->pluck('id')->all();
    }
    public function test_country_and_city_aliases_match_global_feed_locations(): void
    {
        $this->assertSame([1,6,10],$this->ids(['country'=>'India']));
        $this->assertSame([1],$this->ids(['country'=>'IN','state'=>'Karnataka','city'=>'Bangalore']));
        $this->assertSame([10],$this->ids(['country'=>'IN','city'=>'Delhi']));
        $this->assertSame([2],$this->ids(['country'=>'Canada','city'=>'Vancuover']));
        $this->assertSame([3,4],$this->ids(['country'=>'America']));
        $this->assertSame([4],$this->ids(['country'=>'US','state'=>'Calfiornia']));
        $this->assertSame([5],$this->ids(['country'=>'UAE','city'=>'Dubai']));
        $this->assertSame([6],$this->ids(['country'=>'IN','state'=>'Maharashtra','city'=>'Pune']));
    }
    public function test_regions_and_invalid_parent_combinations(): void
    {
        $this->assertSame([7],$this->ids(['region'=>'Europe']));
        $this->assertSame([9],$this->ids(['country'=>'AU']));
        $this->assertSame([],$this->ids(['country'=>'IN','city'=>'Dallas']));
        $this->assertSame([],$this->ids(['country'=>'US','state'=>'Karnataka']));
        $this->assertSame([],$this->ids(['country'=>'not-a-country']));
        $this->assertSame([1],$this->ids(['location'=>'Bangalore']));
        $this->assertSame([8],$this->ids(['location'=>'Remote']));
    }
    public function test_geography_options_are_cascaded_and_worldwide(): void
    {
        $geo=app(JobGeography::class);
        $this->assertGreaterThanOrEqual(240,count($geo->options([])['countries']));
        $india=$geo->options(['country'=>'IN']);
        $state=collect($india['states'])->firstWhere('label','Karnataka');$this->assertNotNull($state);
        $cities=$geo->options(['country'=>'IN','state'=>$state['value'],'q'=>'Beng']);
        $this->assertNotEmpty($cities['cities']);
        $this->assertEmpty($geo->options([])['states']);
        $this->assertNotContains('IN',array_column($geo->options(['region'=>'Europe'])['countries'],'value'));
    }
    public function test_inactive_jobs_do_not_enter_index(): void
    {
        DB::table('jobs')->where('id',1)->update(['is_active'=>false]);
        $this->assertSame([6,10],$this->ids(['country'=>'IN']));
    }
}