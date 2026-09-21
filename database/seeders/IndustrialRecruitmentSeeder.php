<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\{Company, IndustrialDepartment, IndustrialJobRole, IndustrialJobRoleAlias, IndustrialSector, IndustrialProcess};

class IndustrialRecruitmentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('industrial_sources.feeds') as $source) {
            $company = Company::firstOrNew(['name'=>$source['name']]);
            $company->fill($source + ['slug'=>Str::slug($source['name']), 'country'=>'India', 'sector'=>'Manufacturing', 'sync_enabled'=>true, 'is_active'=>true]);
            $company->save();
        }
        $state = \App\Models\IndustrialState::firstOrCreate(['slug'=>'punjab'], ['name'=>'Punjab','is_active'=>true]);
        $source = 'https://www.sonalika.com/manufacturing-excellence.html';
        $area = \App\Models\IndustrialArea::firstOrCreate(['state_id'=>$state->id,'slug'=>'hoshiarpur-manufacturing-cluster'], [
            'name'=>'Hoshiarpur Manufacturing Cluster','city'=>'Hoshiarpur','district'=>'Hoshiarpur','area_type'=>'industrial_cluster',
            'description'=>'City-level manufacturing cluster; individual facilities are not assumed to belong to a PSIEC estate.',
            'is_active'=>true,'source_name'=>'Sonalika official manufacturing facility','source_url'=>$source,'last_verified_at'=>'2026-09-16','verification_status'=>'company_source']);
        $plant = \App\Models\IndustrialCompany::firstOrCreate(['industrial_area_id'=>$area->id,'slug'=>'sonalika'], [
            'name'=>'Sonalika','plant_name'=>'International Tractors Limited, Hoshiarpur','website'=>'https://www.sonalika.com','industry'=>'Tractor Manufacturing',
            'is_active'=>true,'is_verified'=>true,'source_name'=>'Sonalika official manufacturing facility','source_url'=>$source,
            'last_verified_at'=>'2026-09-16','verification_status'=>'company_source']);
        $sectorIds = IndustrialSector::whereIn('slug',['automobile','auto-components','heavy-engineering'])->pluck('id');
        $plant->sectors()->syncWithoutDetaching($sectorIds);$area->sectorCatalog()->syncWithoutDetaching($sectorIds);
        foreach ([
            ['Tata Motors','Pantnagar','https://www.tatamotors.com','https://www.tatamotors.com/press-releases/tata-motors-flags-off-electric-buses-for-workforce-transportation-in-pantnagar-reiterates-its-commitment-towards-carbon-neutrality/'],
            ['Mahindra and Mahindra','Rudrapur','https://www.mahindra.com','https://www.mahindra.com/annual-report-FY2026/46/'],
        ] as [$name,$city,$website,$evidence]) {
            $state=\App\Models\IndustrialState::firstOrCreate(['slug'=>'uttarakhand'],['name'=>'Uttarakhand','is_active'=>true]);
            $area=\App\Models\IndustrialArea::where('state_id',$state->id)->where('city',$city)->first();
            if (!$area) $area=\App\Models\IndustrialArea::create(['state_id'=>$state->id,'name'=>$city.' Manufacturing Cluster','slug'=>Str::slug($city.' Manufacturing Cluster'),'city'=>$city,'district'=>'Udham Singh Nagar','area_type'=>'industrial_cluster','is_active'=>true,'source_name'=>'Company facility evidence','source_url'=>$evidence,'last_verified_at'=>'2026-09-16','verification_status'=>'company_source']);
            $plant=\App\Models\IndustrialCompany::firstOrCreate(['industrial_area_id'=>$area->id,'slug'=>Str::slug($name)],['name'=>$name,'plant_name'=>$city.' facility','website'=>$website,'industry'=>'Manufacturing','is_active'=>true,'is_verified'=>true,'source_name'=>'Official company facility evidence','source_url'=>$evidence,'last_verified_at'=>'2026-09-16','verification_status'=>'company_source']);
            $plant->sectors()->syncWithoutDetaching($sectorIds);$area->sectorCatalog()->syncWithoutDetaching($sectorIds);
        }
        foreach ([
            ['Happy Forgings','Ludhiana','Ludhiana','https://happyforgingsltd.com','https://happyforgingsltd.com/opportunities/','Multiple Ludhiana facilities; exact site depends on the opening'],
            ['Euro Cast & Forge','Ludhiana','Ludhiana','https://www.eurocastforge.com','https://www.eurocastforge.com/','Casting and forging manufacturer; vacancy feed not yet verified'],
            ['GNA Axles','Mehtiana','Hoshiarpur','https://www.gnaaxles.in','https://www.gnaaxles.in/','Mehtiana facility, Hoshiarpur district; vacancy feed not yet verified'],
        ] as [$name,$city,$district,$website,$evidence,$note]) {
            $state=\App\Models\IndustrialState::firstOrCreate(['slug'=>'punjab'],['name'=>'Punjab','is_active'=>true]);
            $area=\App\Models\IndustrialArea::firstOrCreate(['state_id'=>$state->id,'slug'=>Str::slug($city.' Manufacturing Cluster')],[
                'name'=>$city.' Manufacturing Cluster','city'=>$city,'district'=>$district,'area_type'=>'industrial_cluster','is_active'=>true,
                'description'=>'City-level manufacturer directory; not an assertion of membership in a specific industrial estate.',
                'source_name'=>'Official manufacturer website','source_url'=>$evidence,'last_verified_at'=>'2026-09-16','verification_status'=>'company_source']);
            $plant=\App\Models\IndustrialCompany::firstOrCreate(['industrial_area_id'=>$area->id,'slug'=>Str::slug($name)],[
                'name'=>$name,'website'=>$website,'description'=>$note,'industry'=>'Auto Components and Manufacturing','is_active'=>true,'is_verified'=>true,
                'source_name'=>'Official manufacturer website','source_url'=>$evidence,'last_verified_at'=>'2026-09-16','verification_status'=>'company_source']);
            $plant->sectors()->syncWithoutDetaching($sectorIds);$area->sectorCatalog()->syncWithoutDetaching($sectorIds);
        }
        foreach (['PPC','Material Inward','Packaging','Research and Development','Service','Customer Care'] as $name) {
            IndustrialDepartment::firstOrCreate(['slug'=>Str::slug($name)], ['name'=>$name,'category'=>'Manufacturing support','is_active'=>true]);
        }
        $rows = [
            ['VMC Operator','VMC',['VMC Machine Operator','Vertical Machining Centre Operator'],['Tool Offset','Drawing Reading','Vernier','Micrometer'],['cnc-milling']],
            ['Die Maintenance Fitter','Die Maintenance',['Die Repair Fitter'],['Die Repair','Fitting','Drawing Reading'],['die-maintenance']],
            ['Heavy Machine Shop Technician','Machine Shop',['Heavy Machining Technician'],['Machining','Measuring Instruments','Drawing Reading'],['cnc-turning','cnc-milling']],
            ['Conveyor Technician','Maintenance',['Conveyor Maintenance Technician'],['Conveyor Maintenance','Alignment','Bearings'],['mechanical-maintenance']],
            ['Tool or Die Fixture Technician','Tool Room',['Tool Fixture Technician','Die Fixture Technician'],['Jigs and Fixtures','Fitting','Tool Maintenance'],['tool-making']],
            ['Die Setter Technician','Tool Room',['Die Setter','Press Die Setter'],['Die Setting','Press Setup','Tool Alignment'],['closed-die-forging']],
            ['Tractor Mechanic','Maintenance',['Agricultural Tractor Mechanic'],['Diesel Engines','Hydraulics','Transmission'],['mechanical-maintenance']],
            ['Maintenance Fitter','Maintenance',['Mechanical Maintenance Fitter'],['Fitting','Bearings','Alignment'],['mechanical-maintenance']],
            ['Diesel Mechanic','Diesel Mechanic',['Diesel Engine Mechanic'],['Diesel Engines','Fuel Injection','Engine Diagnostics'],['mechanical-maintenance']],
        ];
        foreach ($rows as [$name,$department,$aliases,$skills,$processes]) {
            $dept = IndustrialDepartment::firstOrCreate(['slug'=>Str::slug($department)], ['name'=>$department,'is_active'=>true]);
            $role = IndustrialJobRole::firstOrCreate(['slug'=>Str::slug($name)], ['name'=>$name,'department_id'=>$dept->id,'is_active'=>true]);
            foreach ($aliases as $alias) IndustrialJobRoleAlias::firstOrCreate(['slug'=>Str::slug($alias)], ['name'=>$alias,'job_role_id'=>$role->id]);
            $profile = $role->profile()->firstOrCreate([], ['source_name'=>'Industrial trade learning dictionary; requirements vary by employer']);
            $profile->update(['skills'=>array_values(array_unique(array_merge($profile->skills ?? [],$skills))), 'qualifications'=>array_values(array_unique(array_merge($profile->qualifications ?? [],['ITI','Diploma'])))]);
            $role->sectors()->syncWithoutDetaching(IndustrialSector::whereIn('slug',['automobile','auto-components','forging','heavy-engineering'])->pluck('id'));
            $role->processes()->syncWithoutDetaching(IndustrialProcess::whereIn('slug',$processes)->pluck('id'));
        }
    }
}
