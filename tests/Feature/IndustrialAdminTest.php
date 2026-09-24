<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackActivity;
use App\Models\IndustrialArea;
use App\Models\IndustrialCompany;
use App\Models\IndustrialDepartment;
use App\Models\IndustrialJob;
use App\Models\IndustrialState;
use App\Models\User;
use App\Services\IndustrialCsvImporter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IndustrialAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.foreign_key_constraints' => true]);
        DB::purge('sqlite');
        (require database_path('migrations/2026_09_11_000001_create_industrial_directory.php'))->up();
        (require database_path('migrations/2026_09_12_000001_create_industrial_taxonomy.php'))->up();
        (require database_path('migrations/2026_09_12_000002_add_industrial_career_profiles.php'))->up();
        (require database_path('migrations/2026_09_14_000001_expand_industrial_visual_assets.php'))->up();
        $this->withoutMiddleware(TrackActivity::class);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function owner(): void
    {
        $user = new User(['name' => 'Test owner', 'email' => config('owner.email'), 'role' => 'admin', 'role_code' => 2]);
        $user->id = 1;
        $this->actingAs($user);
    }

    private function area(): IndustrialArea
    {
        $state = IndustrialState::create(['name' => 'Punjab', 'slug' => 'punjab']);

        return $state->areas()->create(['name' => 'Focal Point', 'slug' => 'focal-point', 'city' => 'Ludhiana', 'district' => 'Ludhiana']);
    }

    public function test_every_admin_endpoint_requires_owner_authorization(): void
    {
        $this->get('/admin/industrial-areas')->assertRedirect('/login');
        $user = new User(['name' => 'Employer', 'role' => 'employer', 'role_code' => 0]);
        $user->id = 2;
        $this->actingAs($user);
        foreach (['', '/sources', '/cities', '/lookups', '/states/create'] as $path) {
            $this->get('/admin/industrial-areas'.$path)->assertForbidden();
        }
        $this->post('/admin/industrial-areas/states', ['name' => 'Forbidden'])->assertForbidden();
        $this->post('/admin/industrial-areas/import')->assertForbidden();
        $this->patch('/admin/industrial-areas/cities')->assertForbidden();
        $this->assertSame(0, IndustrialState::count());

        $rogueOwner = new User(['name' => 'Other owner', 'email' => 'other-owner@example.test', 'role' => 'admin', 'role_code' => 2]);
        $rogueOwner->id = 3;
        $this->actingAs($rogueOwner);
        $this->get('/admin/industrial-areas')->assertForbidden();
    }

    public function test_owner_registration_is_disabled(): void
    {
        $this->get('/owner/register')->assertNotFound();
        $this->post('/owner/register', [])->assertNotFound();
    }

    public function test_owner_can_manage_all_record_types_and_review_sources(): void
    {
        $this->owner();
        foreach (IndustrialCsvImporter::TYPES as $type) {
            $this->get('/admin/industrial-areas?type='.$type)->assertOk()->assertSee('Add record');
            $this->get('/admin/industrial-areas/'.$type.'/create')->assertOk()->assertSee('Save record');
        }
        $this->post('/admin/industrial-areas/states', ['name' => 'Punjab', 'code' => 'PB', 'is_active' => '1', 'verification_status' => 'unverified'])->assertRedirect();
        $state = IndustrialState::firstOrFail();
        $this->post('/admin/industrial-areas/industrial_areas', ['state_slug' => 'punjab', 'name' => 'Focal Point', 'city' => 'Ludhiana', 'district' => 'Ludhiana', 'sectors' => 'Engineering;Textile'])->assertRedirect();
        $area = IndustrialArea::firstOrFail();
        $this->assertSame($state->id, $area->state_id);
        $this->put('/admin/industrial-areas/industrial_areas/'.$area->id, ['state_slug' => 'punjab', 'name' => 'Updated Focal Point', 'slug' => 'updated-focal-point', 'city' => 'Ludhiana', 'is_active' => '0'])->assertRedirect();
        $this->assertSame(1, IndustrialArea::count());
        $this->assertSame('updated-focal-point', $area->fresh()->slug);
        $this->assertFalse($area->fresh()->is_active);
        $this->get('/admin/industrial-areas/sources?status=unverified')->assertOk()->assertSee('Updated Focal Point');
        $this->get('/admin/industrial-areas/cities')->assertOk()->assertSee('Ludhiana');
        $this->get('/admin/industrial-areas/not-a-table/create')->assertNotFound();
    }

    public function test_company_verification_requires_evidence_and_controls_public_visibility(): void
    {
        $this->owner();
        $area = $this->area();
        $data = ['state_slug' => 'punjab', 'area_slug' => 'focal-point', 'name' => 'Example Works', 'slug' => 'example-works', 'facility_type' => 'Warehouse', 'is_active' => '1', 'is_verified' => '1', 'verification_status' => 'company_source'];
        $this->postJson('/admin/industrial-areas/industrial_companies', $data)->assertUnprocessable();
        $this->assertSame(0, IndustrialCompany::count());
        $data += ['source_name' => 'Company facility page', 'source_url' => 'https://example.com/warehouse', 'last_verified_at' => now()->subMinute()->format('Y-m-d H:i:s')];
        $this->post('/admin/industrial-areas/industrial_companies', $data)->assertRedirect();
        $company = IndustrialCompany::firstOrFail();
        $this->assertSame(1, IndustrialCompany::visible()->count());
        $this->get('/industrial-areas/state/punjab/focal-point/company/example-works')->assertOk()->assertSee('Warehouse');
        $data['is_active'] = '0';
        $this->put('/admin/industrial-areas/industrial_companies/'.$company->id, $data)->assertRedirect();
        $this->assertSame(0, IndustrialCompany::visible()->count());
        $this->get('/admin/industrial-areas/industrial_companies/'.$company->id.'/edit')->assertOk()->assertSee('Example Works');
    }

    public function test_admin_import_supports_dry_run_and_atomic_errors(): void
    {
        $this->owner();
        $csv = fn () => UploadedFile::fake()->createWithContent('states.csv', "name,code\nPunjab,PB\n");
        $this->post('/admin/industrial-areas/import', ['type' => 'states', 'dry_run' => '1', 'csv' => $csv()])->assertRedirect()->assertSessionHas('status', 'Validated 1 rows. No changes saved.');
        $this->assertSame(0, IndustrialState::count());
        $this->post('/admin/industrial-areas/import', ['type' => 'states', 'dry_run' => '0', 'csv' => $csv()])->assertRedirect();
        $this->assertSame(1, IndustrialState::count());
        $bad = UploadedFile::fake()->createWithContent('bad.csv', "name,code\nGujarat,GJ\nBad State,TOO-LONG-CODE\n");
        $this->postJson('/admin/industrial-areas/import', ['type' => 'states', 'dry_run' => '0', 'csv' => $bad])->assertUnprocessable();
        $this->assertSame(1, IndustrialState::count());
    }

    public function test_locations_can_be_corrected_without_changing_other_states(): void
    {
        $this->owner();
        $area = $this->area();
        $other = IndustrialState::create(['name' => 'Other State', 'slug' => 'other']);
        $otherArea = $other->areas()->create(['name' => 'Other', 'slug' => 'other', 'city' => 'Ludhiana', 'district' => 'Ludhiana']);
        $this->patch('/admin/industrial-areas/cities', ['state_id' => $area->state_id, 'old_city' => 'Ludhiana', 'old_district' => 'Ludhiana', 'city' => 'Updated City', 'district' => 'Updated District'])->assertRedirect();
        $this->assertSame('Updated City', $area->fresh()->city);
        $this->assertSame('Ludhiana', $otherArea->fresh()->city);
        $this->getJson('/industrial-areas/ajax?state='.$area->state_id.'&location=Updated+District')->assertOk()->assertJsonPath('options.area.0.id', $area->id);
    }

    public function test_lookup_options_are_scoped_and_do_not_hide_pending_admin_records(): void
    {
        $this->owner();
        $area = $this->area();
        $company = $area->companies()->create(['name' => 'Pending Company', 'slug' => 'pending', 'is_active' => false]);
        $this->getJson('/admin/industrial-areas/lookups?state_slug=punjab&area_slug=focal-point')->assertOk()->assertJsonPath('company_slug.pending', 'Pending Company');
        $this->getJson('/admin/industrial-areas/lookups?state_slug=other&area_slug=focal-point')->assertOk()->assertJsonCount(0, 'company_slug');
        $this->getJson('/industrial-areas/ajax/companies/'.$area->id)->assertOk()->assertExactJson([]);
    }

    public function test_opening_crud_keeps_roles_and_facilities_in_their_parent_hierarchy(): void
    {
        $this->owner();
        $area = $this->area();
        $department = IndustrialDepartment::create(['name' => 'CNC', 'slug' => 'cnc']);
        $role = $department->roles()->create(['name' => 'CNC Operator', 'slug' => 'cnc-operator']);
        $data = ['external_id' => 'admin:1', 'state_slug' => 'punjab', 'area_slug' => 'focal-point', 'department_slug' => 'cnc', 'role_slug' => 'cnc-operator', 'job_title' => 'CNC Operator', 'salary_min' => '15000', 'salary_max' => '25000', 'skills' => 'CNC;VMC', 'is_active' => '1'];
        $this->post('/admin/industrial-areas/industrial_jobs', $data)->assertSessionHasNoErrors()->assertRedirect();
        $job = IndustrialJob::firstOrFail();
        $this->assertSame($role->id, $job->job_role_id);
        $this->assertSame(1, IndustrialJob::live()->count());
        $this->postJson('/admin/industrial-areas/industrial_jobs', $data)->assertUnprocessable();
        IndustrialDepartment::create(['name' => 'Accounts', 'slug' => 'accounts']);
        $this->putJson('/admin/industrial-areas/industrial_job_roles/'.$role->id, ['name' => 'CNC Operator', 'slug' => 'cnc-operator', 'department_slug' => 'accounts'])->assertUnprocessable();
        $this->assertSame($department->id, $role->fresh()->department_id);
        $data['salary_min'] = '30000';
        $this->putJson('/admin/industrial-areas/industrial_jobs/'.$job->id, $data)->assertUnprocessable();
        $data['salary_min'] = '15000';
        $data['is_active'] = '0';
        $this->put('/admin/industrial-areas/industrial_jobs/'.$job->id, $data)->assertRedirect();
        $this->assertSame(0, IndustrialJob::live()->count());
    }

    public function test_malformed_admin_fields_are_validation_errors_and_missing_records_are_404(): void
    {
        $this->owner();
        $this->postJson('/admin/industrial-areas/states', ['name' => ['not', 'scalar']])->assertUnprocessable();
        $this->postJson('/admin/industrial-areas/states', ['name' => 'Punjab', 'is_active' => ['1']])->assertUnprocessable();
        $this->putJson('/admin/industrial-areas/states/9999', ['name' => 'Punjab'])->assertNotFound();
        $this->assertSame(0, IndustrialState::count());
    }
}
