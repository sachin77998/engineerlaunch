<?php
namespace Database\Seeders;
use App\Models\CareerTrack;
use App\Models\CareerRole;
use App\Models\Company;
use App\Models\CompanyFacility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CareerExplorerSeeder extends Seeder {
    public function run(): void
    {
        DB::transaction(function () {
            foreach (config('career_catalog.tracks', []) as $entry) {
                $track = CareerTrack::updateOrCreate(['slug'=>$entry['slug']], collect($entry)->only(['sector','department','name'])->all());
                foreach ($entry['roles'] as $position => $role) {
                    CareerRole::updateOrCreate(['slug'=>$entry['slug'].'-'.Str::slug($role['name'])], [
                        'career_track_id'=>$track->id,'name'=>$role['name'],'job_family'=>$entry['name'],
                        'career_level'=>$role['level'],'skills'=>$role['skills']??[],
                        'next_roles'=>$role['next']??[], 'sort_order'=>$position,
                    ]);
                }
            }
            $references=config('career_catalog.salary_references', []);
            $referenceKeys=array_map(fn($r)=>$r['sector'].'|'.$r['role_name'],$references);
            foreach(DB::table('career_salary_benchmarks')->where('source_name','User-supplied career reference')->get() as $row) {
                if(!in_array($row->sector.'|'.$row->role_name,$referenceKeys,true)) DB::table('career_salary_benchmarks')->where('id',$row->id)->delete();
            }
            foreach ($references as $entry) {
                DB::table('career_salary_benchmarks')->updateOrInsert(
                    ['sector'=>$entry['sector'],'role_name'=>$entry['role_name'],'source_name'=>'User-supplied career reference'],
                    ['experience_label'=>$entry['experience_label'],'salary_label'=>$entry['salary_label'],'currency'=>'INR','period'=>'annual',
                        'verification_status'=>'user_reference','as_of'=>'2026-09-26','updated_at'=>now(),'created_at'=>now()]);
            }
            $facilities=config('career_catalog.facilities', []);
            foreach(config('cyber_city.companies',[]) as $office) $facilities[]=[
                'slug'=>Str::slug($office['name']).'-cyber-city','company'=>$office['name'],'name'=>$office['location'],
                'facility_type'=>'Office','country'=>'India','state'=>'Haryana','city'=>'Gurugram','industrial_area'=>'DLF Cyber City',
                'industry'=>$office['industry'],'products'=>null,'employee_min'=>null,'employee_max'=>null,'employee_scope'=>null,
                'website'=>$office['website'],'careers_url'=>$office['careers_url'],'source_url'=>$office['source_url'],'verified_on'=>'2026-09-26',
            ];
            foreach ($facilities as $entry) {
                $company = Company::firstOrCreate(['name'=>$entry['company']], [
                    'slug'=>Str::slug($entry['company']),'country'=>$entry['country'],'industry'=>$entry['industry'],
                    'website'=>$entry['website'],'careers_url'=>$entry['careers_url'],'is_active'=>true,'sync_enabled'=>false,
                ]);
                if (!$company->careers_url && $entry['careers_url']) $company->update(['careers_url'=>$entry['careers_url']]);
                CompanyFacility::updateOrCreate(['slug'=>$entry['slug']], array_merge(
                    collect($entry)->except(['company','website','careers_url'])->all(), ['company_id'=>$company->id]));
            }
        });
    }
}
