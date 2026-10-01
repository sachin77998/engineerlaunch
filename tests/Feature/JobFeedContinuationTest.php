<?php
namespace Tests\Feature;
use App\Jobs\SyncCompanyJobs;
use App\Models\BatchRun;
use App\Models\BatchRunItem;
use App\Services\BatchAuditService;
use App\Services\JobScraper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
class JobFeedContinuationTest extends TestCase
{
    public function test_large_feed_continues_in_same_batch_and_keeps_accumulated_counts(): void
    {
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
        Schema::create('users',fn(Blueprint $t)=>$t->id());
        Schema::create('companies',function(Blueprint $t){$t->id();$t->string('name');$t->boolean('is_active');$t->boolean('sync_enabled');});
        (require database_path('migrations/2026_08_27_000004_create_batch_audit_tables.php'))->up();
        DB::table('companies')->insert(['id'=>1,'name'=>'Employer','is_active'=>true,'sync_enabled'=>true]);
        $run=BatchRun::create(['batch_type'=>'official_job_ingestion','name'=>'Test','status'=>'queued','total_items'=>1,'pending_items'=>1,'started_at'=>now()]);
        $item=BatchRunItem::create(['batch_run_id'=>$run->id,'company_id'=>1]);
        $scraper=\Mockery::mock(JobScraper::class);
        $scraper->shouldReceive('scrapeCompany')->once()->andReturn(['success'=>true,'continuation'=>true,'jobs_found'=>20,'jobs_added'=>15,'jobs_updated'=>5,'errors'=>[]]);
        [$job,$batch]=(new SyncCompanyJobs(1,$run->id))->withFakeBatch();
        $job->handle($scraper,new BatchAuditService());
        $this->assertCount(1,$batch->added);
        $this->assertInstanceOf(SyncCompanyJobs::class,$batch->added[0]);
        $this->assertSame('pending',$item->fresh()->status);
        $this->assertSame(20,(int)$run->fresh()->records_found);
        $next=\Mockery::mock(JobScraper::class);
        $next->shouldReceive('scrapeCompany')->once()->andReturn(['success'=>true,'continuation'=>false,'jobs_found'=>10,'jobs_added'=>5,'jobs_updated'=>5,'errors'=>[]]);
        [$nextJob]=$batch->added[0]->withFakeBatch();
        $nextJob->handle($next,new BatchAuditService());
        $this->assertSame('successful',$item->fresh()->status);
        $this->assertSame(30,(int)$item->fresh()->records_found);
        $this->assertSame(20,(int)$item->fresh()->records_created);
        $this->assertSame('successful',$run->fresh()->status);
    }
}
