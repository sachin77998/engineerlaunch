<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Job;
use App\Services\Discovery\CompanyClassifier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CompanyDiscoveryAndFeedsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_classifier_maps_trades_to_catalog_subsectors(): void
    {
        $c = new CompanyClassifier();
        $this->assertSame(['Auto Components, Forging & Casting', 'Forging'], $c->classify('Happy Forgings Ltd'));
        $this->assertSame(['Auto Components, Forging & Casting', 'Casting & Die Casting (India)'], $c->classify('KJ Technocast'));
        $this->assertSame(['Steel, Metals & Pipes', 'TMT Bars & Secondary Steel'], $c->classify('Ludhiana Steel Rolling Mills'));
        $this->assertSame(['Banking & Financial Services', 'Fintech, Broking & Market Infrastructure'], $c->classify('Zerodha', 'stock broking'));
        $this->assertSame(['IT Services & Software', 'Indian IT Services'], $c->classify('Geek Informatic'));
        $this->assertSame(['Pharmaceuticals & Healthcare', 'Formulations & Contract Manufacturing'], $c->classify('Unimax Laboratories'));
        $this->assertSame(CompanyClassifier::FALLBACK, $c->classify('Zyxw Pvt Ltd'));
    }

    public function test_openstreetmap_discovery_files_companies_and_plants_under_the_hub(): void
    {
        Http::fake(['overpass-api.de/*' => Http::response(['elements' => [
            ['type' => 'way', 'id' => 1, 'tags' => ['name' => 'Testline Forgings Pvt Ltd', 'industrial' => 'forging', 'website' => 'testline-forgings.example']],
            ['type' => 'node', 'id' => 2, 'tags' => ['name' => 'Sector 8', 'landuse' => 'industrial']],
        ]])]);
        $this->artisan('companies:discover osm --area="PSIEC Industrial Area Hoshiarpur" --now')->assertSuccessful();

        $company = Company::where('name', 'Testline Forgings Pvt Ltd')->firstOrFail();
        $this->assertSame('https://testline-forgings.example', $company->website);
        $this->assertTrue($company->sync_enabled);
        $this->assertTrue($company->categories()->where('name', 'Forging')->exists());
        $this->assertTrue($company->facilities()->where('industrial_area', 'PSIEC Industrial Area Hoshiarpur')->where('city', 'Hoshiarpur')->exists());
        $this->assertFalse(Company::where('name', 'Sector 8')->exists());
    }

    public function test_gleif_registry_import_locates_companies_by_city_and_merges_existing_names(): void
    {
        Company::create(['name' => 'Titan Company', 'slug' => 'titan-company-test', 'country' => 'India', 'is_active' => true]);
        $record = fn (string $lei, string $name, string $city, array $lines) => ['id' => $lei, 'attributes' => ['entity' => [
            'legalName' => ['name' => $name], 'status' => 'ACTIVE', 'registeredAs' => 'U29100HR2001PTC012345',
            'legalAddress' => ['addressLines' => $lines, 'city' => $city, 'region' => 'IN-HR', 'country' => 'IN'],
        ]]];
        Http::fake(['api.gleif.org/*' => Http::response(['meta' => ['pagination' => ['lastPage' => 1]], 'data' => [
            $record('LEI1', 'MANESAR PRECISION FORGINGS PRIVATE LIMITED', 'GURGAON', ['PLOT 12, SECTOR 8, IMT MANESAR']),
            $record('LEI2', 'TITAN COMPANY LIMITED', 'MANESAR', ['SECTOR 3, IMT MANESAR']),
        ]])]);

        $this->artisan('companies:discover gleif --gleif-cities --area=Manesar --gleif-pages=1 --now')->assertSuccessful();

        $forge = Company::where('name', 'Manesar Precision Forgings Private Limited')->firstOrFail();
        $this->assertSame('U29100HR2001PTC012345', $forge->registry_cin);
        $this->assertTrue($forge->categories()->where('name', 'Forging')->exists());
        $this->assertTrue($forge->facilities()->where('city', 'Manesar')->where('state', 'Haryana')->exists());
        $this->assertSame(1, Company::where('name_key', 'titan')->count(), 'registry spelling must merge into the existing company');
    }

    public function test_wikidata_discovery_skips_unlabelled_items(): void
    {
        Http::fake(['query.wikidata.org/*' => Http::response(['results' => ['bindings' => [
            ['item' => ['value' => 'http://www.wikidata.org/entity/Q1'], 'itemLabel' => ['value' => 'Testwave Software Labs'], 'website' => ['value' => 'https://testwave.example/'], 'industryLabel' => ['value' => 'software industry'], 'hqLabel' => ['value' => 'Mohali']],
            ['item' => ['value' => 'http://www.wikidata.org/entity/Q2'], 'itemLabel' => ['value' => 'Q2'], 'website' => ['value' => 'https://q2.example']],
        ]]])]);
        $this->artisan('companies:discover wikidata --country=IN --pages=1 --now')->assertSuccessful();

        $company = Company::where('name', 'Testwave Software Labs')->firstOrFail();
        $this->assertSame('https://testwave.example', $company->website);
        $this->assertSame('Mohali', $company->headquarters);
        $this->assertTrue($company->categories()->where('name', 'Indian IT Services')->exists());
        $this->assertFalse(Company::where('name', 'Q2')->exists());
    }

    public function test_job_feeds_create_public_jobs_linking_to_the_original_posting(): void
    {
        config(['services.jooble.key' => 'test-key', 'services.jooble.host' => 'jooble.org', 'services.jooble.country' => 'United States']);
        Http::fake([
            'www.arbeitnow.com/*' => Http::response(['data' => [[
                'slug' => 'vmc-operator-test-1', 'company_name' => 'Testline Machining GmbH', 'title' => 'VMC Operator', 'description' => '<p>Run VMC machines</p>',
                'remote' => false, 'url' => 'https://www.arbeitnow.com/jobs/vmc-operator-test-1', 'tags' => ['CNC'], 'job_types' => ['full time'], 'location' => 'Berlin', 'created_at' => now()->timestamp,
            ]]]),
            'jooble.org/*' => Http::response(['jobs' => [['id' => 99, 'title' => 'Melter', 'link' => 'https://jooble.org/desc/99', 'location' => 'Ohio']]]),
        ]);
        $this->artisan('jobs:import-feeds --feed=arbeitnow --feed=jooble --queries=1 --now')->assertSuccessful();

        $job = Job::active()->where('source', 'arbeitnow')->where('external_job_id', 'vmc-operator-test-1')->firstOrFail();
        $this->assertSame('VMC Operator', $job->title);
        $this->assertSame('https://www.arbeitnow.com/jobs/vmc-operator-test-1', $job->external_url);
        $this->assertSame('Run VMC machines', $job->description);
        $this->assertSame('EUR', $job->salary_currency);
        $jooble = Job::active()->where('source', 'jooble')->where('external_job_id', '99')->firstOrFail();
        $this->assertSame('Employer (via Jooble)', $jooble->company->name);

        // Re-running updates instead of duplicating.
        $this->artisan('jobs:import-feeds --feed=arbeitnow --now')->assertSuccessful();
        $this->assertSame(1, Job::where('source', 'arbeitnow')->where('external_job_id', 'vmc-operator-test-1')->count());
    }

    public function test_manufacturing_sector_page_shows_plant_flow_and_hubs_on_overview(): void
    {
        app(\App\Services\SectorCatalogImporter::class)->importAll();
        \App\Services\SectorDirectory::flush();
        $this->get('/sectors/sector-auto-components-forging-casting')->assertOk()->assertSee('Gate &amp; Material Inward', false)->assertSee('Tool Room');
        $this->get('/sectors/sector-it-services-software')->assertOk()->assertDontSee('Gate &amp; Material Inward', false);
        $this->get('/sectors')->assertOk()->assertSee('Industrial areas &amp; tech parks', false)->assertSee('IMT Manesar');
    }
}
