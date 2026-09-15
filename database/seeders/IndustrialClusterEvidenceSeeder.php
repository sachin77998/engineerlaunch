<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\IndustrialState;
use App\Models\IndustrialArea;
use App\Models\IndustrialCompany;
use App\Models\IndustrialSector;
use App\Models\IndustrialSubsector;
use App\Models\IndustrialProcess;
class IndustrialClusterEvidenceSeeder extends Seeder {
 public function run(): void {
  $data=json_decode(file_get_contents(storage_path('app/industrial-data/cluster-evidence.json')),true,512,JSON_THROW_ON_ERROR);
  DB::transaction(function()use($data){foreach($data['clusters'] as $cluster){
   $state=IndustrialState::firstOrCreate(['slug'=>Str::slug($cluster['state'])],['name'=>$cluster['state'],'is_active'=>true]);
   $area=IndustrialArea::where('state_id',$state->id)->where('slug',$cluster['slug'])->first();
   if(!$area)$area=IndustrialArea::where('state_id',$state->id)->where('city',$cluster['city'])->where('name','like','%'.$cluster['city'].'%')->first();
   if(!$area)$area=IndustrialArea::create(['state_id'=>$state->id,'slug'=>$cluster['slug'],'name'=>$cluster['name'],'city'=>$cluster['city'],'district'=>$cluster['district'],'is_active'=>true]);
   $area->forceFill(['name'=>$cluster['name'],'hero_image'=>$cluster['hero'],'tagline'=>$cluster['tagline'],'description'=>$cluster['tagline'].'. Explore source-backed facilities, career pathways and recorded live openings. '.($cluster['description_note']??''),'source_name'=>'Industrial ecosystem research: government and company sources','source_url'=>$cluster['source'],'last_verified_at'=>$data['checked_at'],'verification_status'=>'government_source'])->save();
   $sectorIds=IndustrialSector::whereIn('slug',$cluster['sectors'])->pluck('id');$area->sectorCatalog()->syncWithoutDetaching($sectorIds);
   $area->update(['sectors'=>IndustrialSector::whereIn('id',$sectorIds)->pluck('name')->all()]);
   foreach($cluster['companies'] as $record){$main=$record['sources'][0];$company=IndustrialCompany::firstOrCreate(['industrial_area_id'=>$area->id,'slug'=>Str::slug($record['name'])],['name'=>$record['name'],'plant_name'=>$cluster['city'].' facility','facility_type'=>'Factory','description'=>$record['description'],'is_active'=>$record['active'],'is_verified'=>true,'verification_status'=>str_contains($main['url'],'gov.in')?'government_source':'company_source','source_name'=>$main['title'],'source_url'=>$main['url'],'last_verified_at'=>$data['checked_at']]);
    $company->sectors()->syncWithoutDetaching(IndustrialSector::whereIn('slug',$record['sectors'])->pluck('id'));
    $company->processes()->syncWithoutDetaching(IndustrialProcess::whereIn('name',$record['processes'])->pluck('id'));
    $company->subsectors()->syncWithoutDetaching(IndustrialSubsector::whereIn('slug',$record['subsectors'])->pluck('id'));
    foreach($record['sources'] as $source)$company->sources()->updateOrCreate(['source_key'=>sha1($source['url'])],['title'=>$source['title'],'url'=>$source['url'],'source_period'=>$source['period'],'evidence_note'=>$source['note'],'checked_at'=>$data['checked_at']]);
   }
  }});
 }
}
