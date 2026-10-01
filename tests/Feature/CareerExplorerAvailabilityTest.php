<?php
namespace Tests\Feature;

use App\Services\CareerOpportunitySearch;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CareerExplorerAvailabilityTest extends TestCase
{
    public function test_existing_openings_and_reference_tracks_do_not_require_new_career_tables(): void
    {
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array']);
        DB::purge('sqlite');
        Schema::create('companies', function(Blueprint $t) { $t->id(); $t->boolean('is_active')->default(true); });
        Schema::create('jobs', function(Blueprint $t) {
            $t->id(); $t->softDeletes(); $t->boolean('is_active')->default(true);
            $t->string('status')->default('published'); $t->string('job_visibility')->default('public');
            $t->timestamp('expires_at')->nullable(); $t->timestamp('posted_at')->nullable();
        });
        (require database_path('migrations/2026_09_11_000001_create_industrial_directory.php'))->up();
        (require database_path('migrations/2026_09_12_000001_create_industrial_taxonomy.php'))->up();
        $result=app(CareerOpportunitySearch::class)->search([]);
        $this->assertSame(0,$result['jobs']->total());
        $this->assertSame(0,$result['facilities']->total());
        $this->assertNotEmpty($result['tracks']);
        $this->assertNotEmpty($result['tracks']->first()->roles);
        $this->assertFalse(Schema::hasTable('career_tracks'));
    }

    public function test_career_schema_can_be_prepared_twice_without_data_loss(): void
    {
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);
        DB::purge('sqlite');
        Schema::create('companies',fn(Blueprint $t)=>$t->id());
        $migration=require database_path('migrations/2026_09_26_000001_create_career_explorer.php');
        $migration->up();
        DB::table('career_tracks')->insert(['slug'=>'example','sector'=>'Software','department'=>'Engineering','name'=>'Engineering']);
        $migration->up();
        $this->assertSame(1,DB::table('career_tracks')->count());
        foreach(['career_roles','career_salary_benchmarks','company_facilities'] as $table) $this->assertTrue(Schema::hasTable($table));
    }
}
