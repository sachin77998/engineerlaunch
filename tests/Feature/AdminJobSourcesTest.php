<?php
namespace Tests\Feature;
use App\Http\Middleware\TrackActivity;
use App\Models\User;
use App\Services\CareerSourceDetector;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
class AdminJobSourcesTest extends TestCase
{
    protected function setUp(): void {
        parent::setUp();
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);
        DB::purge('sqlite');
        config(['cache.limiter'=>'array']);
        app()->forgetInstance(\Illuminate\Cache\RateLimiter::class);
        $this->withoutMiddleware(TrackActivity::class);
    }
    public function test_source_report_and_refresh_are_owner_only(): void {
        $this->get('/admin/job-sources')->assertRedirect('/login');
        $user=new User(['role'=>'student','role_code'=>1,'email'=>'candidate@example.test']); $user->id=2;
        $this->actingAs($user)->get('/admin/job-sources')->assertForbidden();
        $this->post('/admin/job-sources/refresh')->assertForbidden();
    }
    public function test_owner_can_see_unconfigured_employers_without_audit_tables(): void {
        Schema::create('companies',function(Blueprint $t){$t->id();$t->string('name');});
        Schema::create('jobs',function(Blueprint $t){$t->id();$t->unsignedBigInteger('company_id');$t->boolean('is_active');$t->string('status');$t->timestamp('expires_at')->nullable();$t->softDeletes();});
        $user=new User(['role'=>'admin','role_code'=>2,'email'=>config('owner.email')]);$user->id=1;
        $this->actingAs($user)->get('/admin/job-sources')->assertOk()->assertSee('Infosys')->assertSee('Database deployment is incomplete')->assertSee('Not configured');
        $this->post('/admin/job-sources/refresh')->assertSessionHas('source_status');
    }
    public function test_detection_requires_a_real_supported_board_url(): void {
        $detector=new CareerSourceDetector();
        $this->assertNull($detector->detectFromUrl('https://greenhouse.io.attacker.test/acme')['provider']);
        $this->assertNull($detector->detectFromUrl('https://example.test/api/jobs')['provider']);
        $board=$detector->detectFromUrl('https://acme.wd5.myworkdayjobs.com/en-US/External');
        $this->assertSame('https://acme.wd5.myworkdayjobs.com/wday/cxs/acme/External/jobs',$board['jobs_feed_url']);
    }
}
