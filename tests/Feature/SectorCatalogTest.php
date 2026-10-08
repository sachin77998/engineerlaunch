<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyCategory;
use App\Models\Job;
use App\Models\User;
use App\Services\SectorCatalogImporter;
use App\Services\SectorDirectory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SectorCatalogTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        app(SectorCatalogImporter::class)->importAll();
        SectorDirectory::flush();
    }

    public function test_catalog_builds_sector_hierarchy_and_merges_shared_companies(): void
    {
        $food = CompanyCategory::where('slug', 'sector-food-beverages')->firstOrFail();
        $this->assertTrue($food->children()->where('name', 'Dal & Pulses')->exists());

        $itc = Company::where('slug', 'itc-limited')->firstOrFail();
        $this->assertContains('Sunfeast', $itc->brands);
        $this->assertContains('Fiama', $itc->brands);
        $this->assertSame(1, Company::where('name', 'ITC Limited')->count());

        $maruti = Company::where('slug', 'maruti-suzuki-india')->firstOrFail();
        $this->assertTrue($maruti->facilities()->where('industrial_area', 'IMT Manesar')->exists());
        $this->assertTrue($maruti->sync_enabled);
    }

    public function test_sector_pages_group_companies_and_search_by_brand_and_hub(): void
    {
        $this->get('/sectors')->assertOk()->assertSee('Food &amp; Beverages', false)->assertSee('Dal &amp; Pulses', false);
        $this->get('/sectors/sector-food-beverages')->assertOk()->assertSee('Britannia Industries')->assertSee('Biscuits &amp; Bakery', false);
        $this->get('/sectors?q=santoor')->assertOk()->assertSee('Wipro Consumer Care &amp; Lighting', false);
        $this->get('/sectors?city=Haridwar')->assertOk()->assertSee('Akums Drugs &amp; Pharmaceuticals', false)->assertDontSee('Nemak');
        $this->get('/sectors/sector-auto-components-forging-casting')->assertOk()->assertSee('Core Shooter Machine Operator');
        $this->get('/sectors/not-a-sector')->assertNotFound();
    }

    public function test_employer_assistant_builds_company_profile_then_publishes_job(): void
    {
        $user = User::forceCreate(['name' => 'Plant HR', 'email' => 'hr-assistant@example.test', 'password' => Hash::make('secret-pass'), 'role' => 'employer', 'role_code' => 2]);
        $this->actingAs($user);

        $answers = ['Shivalik Castings Test', 'company', 'sector-auto-components-forging-casting', 'sector-auto-components-forging-casting-casting-die-casting-india',
            '', 'India', 'Punjab', 'Jalandhar', 'Focal Point Extension', '350', 'Grey iron castings', 'Shivalik', 'HR Manager', '+91 9876543210'];
        $this->get('/employer/assistant/company')->assertOk()->assertSee('What is your company name?');
        foreach ($answers as $answer) $this->post('/employer/assistant/company/answer', ['answer' => $answer])->assertRedirect('/employer/assistant/company');
        $this->get('/employer/assistant/company')->assertSee('Save company profile');
        $this->post('/employer/assistant/company/complete')->assertRedirect('/employer/assistant/job');

        $company = $user->employerProfile()->first()->company;
        $this->assertSame('Shivalik Castings Test', $company->name);
        $this->assertTrue($company->categories()->where('name', 'Casting & Die Casting (India)')->exists());
        $this->assertTrue($company->facilities()->where('city', 'Jalandhar')->exists());

        $this->get('/employer/assistant/job')->assertOk()->assertSee('Mould Master Operator');
        $job = ['Melter', 'Foundry & Casting', 'Full Time', 'On-site', '4', '2', '6', 'ITI', 'Induction furnace, pouring', '240000', '360000', 'Punjab', 'Jalandhar', 'Operate induction furnace and pour metal.', 'publish'];
        foreach ($job as $answer) $this->post('/employer/assistant/job/answer', ['answer' => $answer])->assertRedirect('/employer/assistant/job');
        $this->post('/employer/assistant/job/complete')->assertRedirect(route('employer.dashboard'));

        $posted = Job::where('employer_id', $user->id)->latest('id')->firstOrFail();
        $this->assertSame('Melter', $posted->title);
        $this->assertSame('published', $posted->status);
        $this->assertSame(4, (int) $posted->vacancies);
        $this->assertTrue($posted->skills()->where('name', 'Induction furnace')->exists());
    }

    public function test_assistant_rejects_answers_outside_the_offered_options(): void
    {
        $user = User::forceCreate(['name' => 'HR', 'email' => 'hr-options@example.test', 'password' => Hash::make('secret-pass'), 'role' => 'employer', 'role_code' => 2]);
        $this->actingAs($user)->post('/employer/assistant/company/answer', ['answer' => 'Acme']);
        $this->post('/employer/assistant/company/answer', ['answer' => 'pirate_ship'])->assertSessionHasErrors('answer');
        $this->get('/employer/assistant/job')->assertRedirect('/employer/assistant/company');
    }
}
