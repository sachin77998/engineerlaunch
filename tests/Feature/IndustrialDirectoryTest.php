<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackActivity;
use App\Models\IndustrialArea;
use App\Models\IndustrialCompany;
use App\Models\IndustrialDepartment;
use App\Models\IndustrialJob;
use App\Models\IndustrialJobRole;
use App\Models\IndustrialState;
use App\Services\IndustrialCsvImporter;
use App\Services\IndustrialIntelligence;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IndustrialDirectoryTest extends TestCase
{
    private array $csvFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        // Never migrate, truncate or refresh the configured application database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.foreign_key_constraints' => true]);
        DB::purge('sqlite');
        (require database_path('migrations/2026_09_11_000001_create_industrial_directory.php'))->up();
        (require database_path('migrations/2026_09_12_000001_create_industrial_taxonomy.php'))->up();
        (require database_path('migrations/2026_09_12_000002_add_industrial_career_profiles.php'))->up();
        (require database_path('migrations/2026_09_14_000001_expand_industrial_visual_assets.php'))->up();
        (require database_path('migrations/2026_09_15_155357_add_careers_url_to_industrial_companies_table.php'))->up();
        $this->withoutMiddleware(TrackActivity::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->csvFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function csv(string $contents): string
    {
        $file = tempnam(sys_get_temp_dir(), 'industrial-test-');
        file_put_contents($file, $contents);
        $this->csvFiles[] = $file;

        return $file;
    }

    private function fixture(): array
    {
        $state = IndustrialState::create(['name' => 'Punjab', 'slug' => 'punjab']);
        $area = $state->areas()->create(['name' => 'Ludhiana Industrial Focal Point', 'slug' => 'focal-point', 'city' => 'Ludhiana', 'district' => 'Ludhiana', 'is_featured' => true]);
        $company = $area->companies()->create(['name' => 'Happy Forgings', 'slug' => 'happy-forgings', 'is_verified' => true, 'verification_status' => 'company_source', 'source_name' => 'Test fixture', 'source_url' => 'https://example.com/facility', 'last_verified_at' => now(), 'facility_type' => 'Factory']);
        $department = IndustrialDepartment::create(['name' => 'CNC', 'slug' => 'cnc']);
        $role = $department->roles()->create(['name' => 'CNC Operator', 'slug' => 'cnc-operator']);
        $job = IndustrialJob::create(['industrial_area_id' => $area->id, 'industrial_company_id' => $company->id, 'department_id' => $department->id, 'job_role_id' => $role->id, 'external_id' => 'test:1', 'job_title' => 'CNC Operator', 'experience_min' => 0, 'experience_max' => 2, 'salary_min' => 18000, 'salary_max' => 25000, 'qualification' => 'ITI / Diploma', 'skills' => ['CNC', 'VMC'], 'application_deadline' => now()->addDay()]);

        return compact('state', 'area', 'company', 'department', 'role', 'job');
    }

    public function test_public_directory_searches_companies_and_multiword_job_queries(): void
    {
        $f = $this->fixture();
        $this->get('/industrial-areas?search=Happy+Forgings')->assertOk()->assertSee('Ludhiana Industrial Focal Point')->assertSee('Happy Forgings');
        $response = $this->getJson('/industrial-areas/ajax?search=CNC+Operator+jobs+near+Ludhiana')->assertOk();
        $this->assertStringContainsString('CNC Operator', $response->json('html'));
        $this->assertStringContainsString('Happy Forgings', $response->json('html'));
        $this->get(route('industrial.show', ['punjab', 'focal-point']))->assertOk()->assertSee('Hiring now')->assertSee('1 live openings')->assertSee('cnc');
        $this->get(route('industrial.company', ['punjab', 'focal-point', 'happy-forgings']))->assertOk()->assertSee('Company Verified')->assertSee('Factory');
        $this->get(route('industrial.jobs.show', $f['job']))->assertOk()->assertSee('CNC Operator');
    }

    public function test_ajax_routes_are_not_caught_by_slugs_and_reject_cross_state_selection(): void
    {
        $f = $this->fixture();
        $other = IndustrialState::create(['name' => 'Gujarat', 'slug' => 'gujarat']);
        $this->getJson('/industrial-areas/ajax/cities?state_id='.$f['state']->id)->assertOk()->assertExactJson(['Ludhiana']);
        $this->getJson('/industrial-areas/ajax/areas?state_id='.$f['state']->id.'&city=Ludhiana')->assertOk()->assertJsonCount(1);
        $this->getJson('/industrial-areas/ajax/companies/'.$f['area']->id)->assertOk()->assertJsonCount(1);
        $this->getJson('/industrial-areas/ajax?state='.$other->id.'&area='.$f['area']->id)->assertUnprocessable()->assertJsonValidationErrors('area');
        $this->getJson('/industrial-areas/ajax?state[]=1')->assertUnprocessable();
        $this->getJson('/industrial-areas/ajax/cities')->assertUnprocessable();
    }

    public function test_inactive_parents_unverified_occupants_and_expired_jobs_are_hidden(): void
    {
        $f = $this->fixture();
        $f['job']->update(['application_deadline' => now()->subMinute()]);
        $this->assertSame(0, IndustrialJob::live()->count());
        $this->get(route('industrial.jobs.show', $f['job']))->assertNotFound();
        $f['job']->update(['application_deadline' => null]);
        $f['company']->update(['is_verified' => false]);
        $this->getJson('/industrial-areas/ajax/companies/'.$f['area']->id)->assertOk()->assertExactJson([]);
        $this->assertSame(0, IndustrialJob::live()->count());
        $this->get(route('industrial.company', ['punjab', 'focal-point', 'happy-forgings']))->assertNotFound();
        $f['state']->update(['is_active' => false]);
        $this->get(route('industrial.show', ['punjab', 'focal-point']))->assertNotFound();
        $this->getJson('/industrial-areas/ajax/cities?state_id='.$f['state']->id)->assertNotFound();
    }

    public function test_import_is_idempotent_handles_bom_multiline_and_semicolon_sectors(): void
    {
        IndustrialState::create(['name' => 'Punjab', 'slug' => 'punjab']);
        $path = $this->csv("\xEF\xBB\xBFstate,city,district,name,area_type,sectors,description\nPunjab,Ludhiana,Ludhiana,Focal Point,industrial_area,Textile;Engineering,\"First line\nSecond line\"\n");
        $importer = app(IndustrialCsvImporter::class);
        $this->assertSame(1, $importer->import('industrial_areas', $path));
        $this->assertSame(1, $importer->import('industrial_areas', $path));
        $this->assertSame(1, IndustrialArea::count());
        $this->assertSame(['Textile', 'Engineering'], IndustrialArea::first()->sectors);
        $this->assertStringContainsString("\n", IndustrialArea::first()->description);
    }

    public function test_failed_import_rolls_back_every_row_and_dry_run_saves_nothing(): void
    {
        $importer = app(IndustrialCsvImporter::class);
        $this->assertSame(1, $importer->import('states', $this->csv("name,code\nPunjab,PB\n"), true));
        $this->assertSame(0, IndustrialState::count());
        $path = $this->csv("name,code\nPunjab,PB\nBad State,CODE-TOO-LONG\n");
        try {
            $importer->import('states', $path);
            $this->fail('Invalid row was accepted.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('row 3', $e->getMessage());
        }
        $this->assertSame(0, IndustrialState::count());
        $this->artisan('industrial:import', ['type' => 'states', '--path' => $path])->assertExitCode(1);
    }

    public function test_import_rejects_company_area_mismatch_and_unsafe_urls(): void
    {
        $f = $this->fixture();
        $f['state']->areas()->create(['name' => 'Other', 'slug' => 'other', 'city' => 'Rajpura']);
        $path = $this->csv("external_id,state_slug,area_slug,company_slug,job_title\ntest:2,punjab,other,happy-forgings,Operator\n");
        try {
            app(IndustrialCsvImporter::class)->import('industrial_jobs', $path);
            $this->fail('Cross-area company was accepted.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Company does not belong', $e->getMessage());
        }
        $path = $this->csv("state,area,company,website\nPunjab,Focal Point,Bad Company,javascript:alert(1)\n");
        try {
            app(IndustrialCsvImporter::class)->import('industrial_companies', $path);
            $this->fail('Unsafe URL accepted.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('website', $e->getMessage());
        }
        $this->assertSame(1, IndustrialJob::count());
    }

    public function test_salary_experience_qualification_and_skill_filters(): void
    {
        $f = $this->fixture();
        $this->get('/industrial-areas?experience=fresher&qualification=ITI&salary=10000-20000&skill=CNC')->assertOk()->assertSee('CNC Operator');
        $response = $this->getJson('/industrial-areas/ajax?salary=50000%2B')->assertOk();
        $this->assertStringContainsString('No live openings match', $response->json('html'));
        $f['job']->update(['salary_period' => 'annual', 'salary_min' => 240000, 'salary_max' => 360000]);
        $response = $this->getJson('/industrial-areas/ajax?salary=20000-30000')->assertOk();
        $this->assertStringNotContainsString('No live openings match', $response->json('html'));
    }

    public function test_directory_data_imports_preserving_supplied_verification_without_creating_openings(): void
    {
        $this->seed(\Database\Seeders\IndustrialDirectorySeeder::class);
        $seededAreas = IndustrialArea::count();
        $seededCompanies = IndustrialCompany::count();
        $importer = app(IndustrialCsvImporter::class);
        $this->assertSame(134, $importer->import('industrial_areas', storage_path('app/industrial-data/industrial_areas.csv')));
        $this->assertSame(81, $importer->import('industrial_companies', storage_path('app/industrial-data/industrial_companies.csv')));
        $this->assertSame(36, IndustrialState::count());
        $this->assertSame($seededAreas, IndustrialArea::count());
        $this->assertSame($seededCompanies, IndustrialCompany::count());
        $this->assertGreaterThanOrEqual(43, IndustrialCompany::visible()->count());
        $this->assertSame(0, IndustrialJob::count());
        $this->assertGreaterThan(60, IndustrialJobRole::count());
        $this->get('/industrial-areas')->assertOk()->assertSee('Popular Industrial Hubs')->assertSee('Unverified starter record');
        $this->artisan('industrial:import-areas', ['--dry-run' => true])->assertExitCode(0);
    }

    public function test_scoped_slugs_and_nearby_distance_cross_state_borders(): void
    {
        $f = $this->fixture();
        $f['area']->update(['latitude' => 30.9, 'longitude' => 75.85]);
        $state = IndustrialState::create(['name' => 'Haryana', 'slug' => 'haryana']);
        $area = $state->areas()->create(['name' => 'Second Focal Point', 'slug' => 'focal-point', 'city' => 'Nearby', 'latitude' => 30.91, 'longitude' => 75.86]);
        $this->get('/industrial-areas/focal-point')->assertNotFound();
        $this->get('/industrial-areas/state/haryana/focal-point')->assertOk()->assertSee('Second Focal Point');
        $nearby = app(IndustrialIntelligence::class)->nearby($f['area']);
        $this->assertSame($area->id, $nearby['nearbyAreas']->first()->id);
        $this->assertLessThan(2, $nearby['nearbyAreas']->first()->distance_km);
    }

    public function test_department_role_mismatch_and_missing_verification_evidence_are_rejected(): void
    {
        $f = $this->fixture();
        IndustrialDepartment::create(['name' => 'Accounts', 'slug' => 'accounts']);
        $path = $this->csv("external_id,state_slug,area_slug,department_slug,role_slug,job_title\ntest:2,punjab,focal-point,accounts,cnc-operator,CNC Operator\n");
        try {
            app(IndustrialCsvImporter::class)->import('industrial_jobs', $path);
            $this->fail('Mismatched role accepted.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Role does not belong', $e->getMessage());
        }
        $path = $this->csv("state,area,company,is_verified,verification_status\nPunjab,Focal Point,Example,1,company_source\n");
        try {
            app(IndustrialCsvImporter::class)->import('industrial_companies', $path);
            $this->fail('Verification without evidence accepted.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('require source_name', $e->getMessage());
        }
    }

    public function test_pagination_preserves_filter_and_html_escapes_imported_content(): void
    {
        $f = $this->fixture();
        $f['area']->update(['description' => '<img src=x onerror=alert(1)>']);
        for ($i = 2; $i <= 14; $i++) {
            IndustrialJob::create(['industrial_area_id' => $f['area']->id, 'external_id' => 'page:'.$i, 'job_title' => 'Operator '.$i]);
        }
        $response = $this->getJson('/industrial-areas/ajax?state='.$f['state']->id)->assertOk();
        $this->assertStringContainsString('jobs_page=2', $response->json('html'));
        $this->assertStringContainsString('&lt;img', $response->json('html'));
        $this->assertStringNotContainsString('<img src=x', $response->json('html'));
    }

    public function test_migration_can_be_rolled_back_and_reapplied(): void
    {
        $migration = require database_path('migrations/2026_09_11_000001_create_industrial_directory.php');
        $migration->down();
        $migration->up();
        $this->assertSame(0, IndustrialJob::count());
    }

    public function test_taxonomy_seed_is_complete_idempotent_and_uses_real_assets(): void
    {
        $this->seed(\Database\Seeders\IndustrialTaxonomySeeder::class);
        $counts = [\App\Models\IndustrialSector::count(), \App\Models\IndustrialProcess::count(), IndustrialJobRole::count(), \App\Models\IndustrialAsset::count()];
        $this->seed(\Database\Seeders\IndustrialTaxonomySeeder::class);
        $this->assertSame($counts, [\App\Models\IndustrialSector::count(), \App\Models\IndustrialProcess::count(), IndustrialJobRole::count(), \App\Models\IndustrialAsset::count()]);
        $this->assertSame(33, $counts[0]);
        $this->assertGreaterThan(100, $counts[1]);
        $this->assertGreaterThan(150, $counts[2]);
        $this->assertSame(39, $counts[3]);
        foreach (\App\Models\IndustrialAsset::all() as $asset) {
            $this->assertFileExists(public_path($asset->path));
        }
        $this->assertSame(0, IndustrialJob::count());
        $this->assertSame(0, IndustrialCompany::count());
        $migration = require database_path('migrations/2026_09_12_000001_create_industrial_taxonomy.php');
        (require database_path('migrations/2026_09_14_000001_expand_industrial_visual_assets.php'))->down();
        (require database_path('migrations/2026_09_12_000002_add_industrial_career_profiles.php'))->down();
        $migration->down();
        $this->assertSame($counts[2], IndustrialJobRole::count());
        $migration->up();
        (require database_path('migrations/2026_09_12_000002_add_industrial_career_profiles.php'))->up();
        (require database_path('migrations/2026_09_14_000001_expand_industrial_visual_assets.php'))->up();
        $this->seed(\Database\Seeders\IndustrialTaxonomySeeder::class);
        $this->get('/industrial-areas')->assertOk()->assertSee('Industrial Career Dictionary')->assertSee('images/industries/steel/steel1.jpeg');
    }

    public function test_taxonomy_rejects_cross_sector_processes_and_filters_mapped_companies(): void
    {
        $f = $this->fixture();
        $this->seed(\Database\Seeders\IndustrialTaxonomySeeder::class);
        $sector = \App\Models\IndustrialSector::where('slug', 'gear-manufacturing')->firstOrFail();
        $sub = \App\Models\IndustrialSubsector::where('slug', 'gear-manufacturing-transmission-gears')->firstOrFail();
        $process = \App\Models\IndustrialProcess::where('slug', 'gear-hobbing')->firstOrFail();
        $query = 'sector='.$sector->id.'&subsector='.$sub->id.'&process='.$process->id;
        $r = $this->getJson('/industrial-areas/ajax?'.$query)->assertOk();
        $this->assertStringNotContainsString('Happy Forgings', $r->json('html'));
        $f['company']->sectors()->attach($sector);
        $f['company']->subsectors()->attach($sub);
        $f['company']->processes()->attach($process);
        $r = $this->getJson('/industrial-areas/ajax?'.$query)->assertOk();
        $this->assertStringContainsString('Happy Forgings', $r->json('html'));
        $this->assertStringContainsString('Gear Hobbing Operator', $r->json('taxonomyHtml'));
        $this->assertStringContainsString('/gear/gear6.jpeg', $r->json('hero'));
        $other = \App\Models\IndustrialProcess::where('slug', 'spinning')->firstOrFail();
        $this->getJson('/industrial-areas/ajax?sector='.$sector->id.'&process='.$other->id)->assertUnprocessable()->assertJsonValidationErrors('process');
        $this->getJson('/industrial-areas/ajax?subsector='.$sub->id)->assertUnprocessable()->assertJsonValidationErrors('subsector');
        $f['company']->update(['is_active' => false]);
        $r = $this->getJson('/industrial-areas/ajax?'.$query)->assertOk();
        $this->assertStringNotContainsString('Happy Forgings', $r->json('html'));
    }

    public function test_role_alias_finds_canonical_opening(): void
    {
        $f = $this->fixture();
        $f['role']->aliases()->create(['name' => 'Computer Numerical Control Operator', 'slug' => 'computer-numerical-control-operator']);
        $r = $this->getJson('/industrial-areas/ajax?search=Computer+Numerical+Control')->assertOk();
        $this->assertStringContainsString('CNC Operator', $r->json('html'));
    }

    public function test_career_matches_rank_titles_and_preserve_location_constraints(): void
    {
        $f = $this->fixture();
        $this->seed(\Database\Seeders\IndustrialTaxonomySeeder::class);
        $setter = IndustrialJobRole::where('name','CNC Setter')->firstOrFail();
        $vmc = IndustrialJobRole::where('name','VMC Operator')->firstOrFail();
        $match = app(\App\Services\IndustrialCareerMatcher::class)->match(['cnc','setter','ludhiana']);
        $this->assertSame(100, $match['matches'][$setter->id]['score']);
        $this->assertSame(90, $match['matches'][$f['role']->id]['score']);
        $this->assertArrayHasKey($vmc->id, $match['matches']);
        $this->assertSame(['ludhiana'], $match['remaining']);
        $this->get('/industrial-areas?search=CNC+Setter+near+Ludhiana')->assertOk()->assertViewHas('jobs', fn($jobs)=>$jobs->total()===1);
        $this->get('/industrial-areas?search=CNC+Setter+near+Delhi')->assertOk()->assertViewHas('jobs', fn($jobs)=>$jobs->total()===0);
        $f['job']->update(['application_deadline'=>now()->subDay()]);
        $this->get('/industrial-areas?search=CNC+Setter')->assertOk()->assertViewHas('jobs', fn($jobs)=>$jobs->total()===0);
    }

    public function test_industry_counts_ignore_expired_openings_and_inactive_images(): void
    {
        $f = $this->fixture();
        $this->seed(\Database\Seeders\IndustrialTaxonomySeeder::class);
        $sector = \App\Models\IndustrialSector::where('slug','forging')->firstOrFail();
        $f['company']->sectors()->attach($sector);
        $response = $this->get('/industrial-areas?sector='.$sector->id)->assertOk();
        $tile = $response->viewData('browseSectors')->firstWhere('id',$sector->id);
        $this->assertSame(1, (int) $tile->company_count);
        $this->assertSame(1, $tile->opening_count);
        \App\Models\IndustrialAsset::where('sector_id',$sector->id)->update(['is_active'=>false]);
        $f['job']->update(['application_deadline'=>now()->subDay()]);
        $response = $this->get('/industrial-areas?sector='.$sector->id)->assertOk();
        $this->assertNull($response->viewData('heroUrl'));
        $this->assertSame(0,$response->viewData('browseSectors')->firstWhere('id',$sector->id)->opening_count);
    }

    public function test_fitter_pathway_and_cluster_evidence_are_idempotent_without_creating_jobs(): void
    {
        $this->seed(\Database\Seeders\IndustrialTaxonomySeeder::class);
        $this->seed(\Database\Seeders\IndustrialCareerPathwaySeeder::class);
        $match=app(\App\Services\IndustrialCareerMatcher::class)->match(['iti','fitter']);
        $fitter=IndustrialJobRole::where('name','Maintenance Fitter')->firstOrFail();
        $this->assertArrayHasKey($fitter->id,$match['matches']);
        $this->seed(\Database\Seeders\IndustrialClusterEvidenceSeeder::class);
        $count=IndustrialCompany::count();
        $company=IndustrialCompany::where('name','Maxxis Rubber India')->firstOrFail();
        $this->assertTrue($company->sources()->exists());
        $company->update(['is_active'=>false]);
        $this->seed(\Database\Seeders\IndustrialClusterEvidenceSeeder::class);
        $this->assertSame($count,IndustrialCompany::count());
        $this->assertFalse($company->fresh()->is_active);
        $this->assertSame(0,IndustrialJob::count());
        $this->get('/industrial-areas/state/haryana/manesar-industrial-area')->assertOk()->assertSee('IMT Manesar')->assertSee('0 live openings');
    }

    public function test_jalandhar_recruitment_seed_is_idempotent_and_publicly_accessible(): void
    {
        \Illuminate\Support\Facades\Schema::create('companies', function (\Illuminate\Database\Schema\Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('careers_url')->nullable();
            $table->string('jobs_feed_url')->nullable();
            $table->string('ats_provider')->nullable();
            $table->string('industry')->nullable();
            $table->string('sector')->nullable();
            $table->string('country')->default('India');
            $table->boolean('sync_enabled')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        $this->seed(\Database\Seeders\IndustrialTaxonomySeeder::class);
        $this->seed(\Database\Seeders\IndustrialRecruitmentSeeder::class);

        $area = \App\Models\IndustrialArea::where('slug', 'jalandhar-industrial-area')->firstOrFail();
        $this->assertSame(25, $area->companies()->count());
        $this->assertSame(25, \App\Models\Company::whereIn('name', $area->companies()->pluck('name'))->count());

        $this->get('/industrial-areas/state/punjab/jalandhar-industrial-area')->assertOk();
        $this->get('/industrial-areas/state/punjab/jalandhar-industrial-area/company/ajay-industries')->assertOk();

        $this->seed(\Database\Seeders\IndustrialRecruitmentSeeder::class);
        $this->assertSame(25, $area->companies()->count());
        $this->assertSame(25, \App\Models\Company::whereIn('name', $area->companies()->pluck('name'))->count());
    }
}
