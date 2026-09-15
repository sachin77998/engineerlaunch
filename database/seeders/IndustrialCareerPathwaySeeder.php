<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\IndustrialJobRole;
use App\Models\IndustrialDepartment;
use App\Models\IndustrialSector;
use App\Models\IndustrialProcess;
class IndustrialCareerPathwaySeeder extends Seeder {
 public function run(): void {
  $department=IndustrialDepartment::firstOrCreate(['slug'=>'forging'],['name'=>'Forging','is_active'=>true]);$sector=IndustrialSector::where('slug','forging')->firstOrFail();
  foreach(['Entry level'=>'Production Helper|Furnace Helper|Forging Helper|Machine Operator|Fitter|Welder|Quality Inspector','ITI / Diploma'=>'Forging Technician|CNC Operator|Die Maker|Die Maintenance Technician|Heat Treatment Technician|Maintenance Fitter|Quality Technician','Engineering'=>'Production Engineer|Forging Engineer|Tooling Engineer|Die Design Engineer|Process Engineer|Quality Engineer|Maintenance Engineer|Metallurgical Engineer','Senior'=>'Production Manager|Plant Manager|Quality Manager|Maintenance Manager|Tool Room Manager|Operations Manager'] as $level=>$names)foreach(explode('|',$names) as $name){$role=IndustrialJobRole::firstOrCreate(['slug'=>Str::slug($name)],['name'=>$name,'department_id'=>$department->id,'is_active'=>true]);$role->sectors()->syncWithoutDetaching([$sector->id]);$profile=$role->profile()->firstOrCreate([],['source_name'=>'User-supplied forging career pathway']);$profile->update(['career_level'=>$level]);}
  $fitterNames=['Maintenance Fitter','Machine Fitter','Production Fitter','Assembly Technician','Forging Technician','Foundry Technician','Machine Maintenance Technician'];
  $sectors=IndustrialSector::whereIn('slug',['forging','casting-foundry','steel','automobile','auto-components','heavy-engineering','cnc-machining','construction-equipment'])->pluck('id');
  foreach($fitterNames as $name){$deptName=str_contains($name,'Assembly')?'Assembly':'Maintenance';$dept=IndustrialDepartment::firstOrCreate(['slug'=>Str::slug($deptName)],['name'=>$deptName,'is_active'=>true]);$role=IndustrialJobRole::firstOrCreate(['slug'=>Str::slug($name)],['name'=>$name,'department_id'=>$dept->id,'is_active'=>true]);$profile=$role->profile()->firstOrCreate([],['source_name'=>'User-supplied ITI Fitter career pathway']);$profile->update(['qualifications'=>['ITI Fitter','ITI','Diploma'],'skills'=>['Fitting','Drawing Reading','Mechanical Assembly','Measuring Instruments'],'career_level'=>'ITI / Diploma']);$role->sectors()->syncWithoutDetaching($sectors);$role->processes()->syncWithoutDetaching(IndustrialProcess::whereIn('slug',['mechanical-maintenance','machine-maintenance'])->pluck('id'));}
 }
}
