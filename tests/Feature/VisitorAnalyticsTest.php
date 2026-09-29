<?php
namespace Tests\Feature;

use App\Http\Middleware\TrackActivity;
use App\Services\VisitorAnalytics;
use App\Services\VisitorLocation;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VisitorAnalyticsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default'=>'sqlite', 'database.connections.sqlite.database'=>':memory:', 'cache.default'=>'array',
            'visitor_analytics.timezone'=>'Asia/Kolkata', 'visitor_analytics.geolocation_enabled'=>true,
            'visitor_analytics.dbip_key'=>'free', 'visitor_analytics.daily_lookup_limit'=>450]);
        DB::purge('sqlite');
        (require database_path('migrations/2026_09_29_130000_create_daily_visitors_table.php'))->up();
        Schema::create('activity_logs', function (Blueprint $t) {
            $t->id(); $t->integer('user_id')->nullable(); $t->string('session_id')->nullable();
            foreach (['method','path','action','ip_hash','user_agent'] as $column) $t->string($column)->nullable();
            $t->timestamp('created_at');
        });
        Carbon::setTestNow(Carbon::parse('2026-09-29 12:00:00', 'UTC'));
        Http::swap(new \Illuminate\Http\Client\Factory());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_repeat_visits_are_deduplicated_and_state_totals_include_unknown(): void
    {
        $service = app(VisitorAnalytics::class);
        $match = $service->record('browser-a');
        $service->record('browser-a');
        $service->record('browser-b');
        DB::table('daily_visitors')->where($match)->update(['state'=>'Punjab', 'country_code'=>'IN']);
        $summary = $service->summary('2026-09-29');
        $this->assertSame(2, $summary['visitors']);
        $this->assertSame(3, $summary['page_views']);
        $this->assertSame(2, $summary['states']->count());
        $this->assertSame(30, count($summary['days']));
        $this->assertSame(2, $summary['days'][0]['visitors']);
        $this->assertSame(0, $summary['days'][1]['visitors']);
        $html = view('admin.visitor-analytics', ['visitorAnalytics'=>$summary])->render();
        $this->assertStringContainsString('Punjab', $html);
        $this->assertStringContainsString('Unknown', $html);
    }

    public function test_dates_use_india_midnight_and_repeat_browser_counts_next_day(): void
    {
        $service = app(VisitorAnalytics::class);
        Carbon::setTestNow(Carbon::parse('2026-09-29 18:29:59', 'UTC'));
        $this->assertSame('2026-09-29', $service->record('browser-a')['visit_date']);
        Carbon::setTestNow(Carbon::parse('2026-09-29 18:30:00', 'UTC'));
        $this->assertSame('2026-09-30', $service->record('browser-a')['visit_date']);
        $this->assertSame(1, $service->summary('2026-09-29')['visitors']);
        $this->assertSame(1, $service->summary('2026-09-30')['visitors']);
    }

    public function test_location_is_cached_and_failures_and_private_addresses_stay_unknown(): void
    {
        Http::fake(['api.db-ip.com/*'=>Http::response(['countryCode'=>'IN','stateProv'=>'Punjab'])]);
        $geo = app(VisitorLocation::class);
        $this->assertSame(['country_code'=>'IN','state'=>'Punjab'], $geo->lookup('8.8.8.8'));
        $geo->lookup('8.8.8.8');
        $this->assertSame([], $geo->lookup('127.0.0.1'));
        $this->assertSame([], $geo->lookup('10.0.0.1'));
        Http::assertSentCount(1);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake(['api.db-ip.com/*'=>Http::response(['error'=>'quota exceeded'], 429)]);
        $this->assertSame([], $geo->lookup('1.1.1.1'));
        $this->assertSame([], $geo->lookup('1.1.1.1'));
        Http::assertSentCount(1);
    }

    public function test_lookup_budget_does_not_stop_visit_counting(): void
    {
        config(['visitor_analytics.daily_lookup_limit'=>0]);
        $service = app(VisitorAnalytics::class);
        $match = $service->record('browser-a');
        $service->locate($match, '8.8.8.8');
        Http::assertNothingSent();
        $this->assertSame(1, $service->summary()['visitors']);
    }

    private function visit(string $path, string $agent = 'Mozilla/5.0', int $status = 200, ?string $cookie = null)
    {
        $request = Request::create($path, 'GET', [], $cookie ? ['portal_visitor'=>$cookie] : [], [], [
            'REMOTE_ADDR'=>'127.0.0.1', 'HTTP_USER_AGENT'=>$agent, 'HTTP_X_FORWARDED_FOR'=>'8.8.8.8',
        ]);
        $request->setLaravelSession(app('session')->driver());
        return app(TrackActivity::class)->handle($request, fn()=>response('<html>OK</html>', $status)->header('Content-Type','text/html'));
    }

    public function test_middleware_counts_public_html_only_and_reuses_visitor_cookie(): void
    {
        $first = $this->visit('/learn');
        $cookie = collect($first->headers->getCookies())->first(fn($c)=>$c->getName()==='portal_visitor');
        $this->assertNotNull($cookie);
        $this->visit('/jobs', 'Mozilla/5.0', 200, $cookie->getValue());
        $this->visit('/admin');
        $this->visit('/api/jobs');
        $this->visit('/login');
        $this->visit('/learn', 'Googlebot');
        $this->visit('/missing', 'Mozilla/5.0', 404);
        $this->assertSame(1, DB::table('daily_visitors')->count());
        $this->assertSame(2, (int)DB::table('daily_visitors')->sum('page_views'));
        $this->assertSame(hash_hmac('sha256','127.0.0.1',config('app.key')), DB::table('activity_logs')->value('ip_hash'));
        Http::assertNothingSent();
    }

    public function test_tracking_failure_does_not_break_public_pages_and_admin_report_is_protected(): void
    {
        Schema::drop('daily_visitors');
        $this->assertSame(200, $this->visit('/learn')->getStatusCode());
        $route = Route::getRoutes()->match(Request::create('/admin'));
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains('admin', $route->gatherMiddleware());
    }
}
