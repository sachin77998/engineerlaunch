<?php
namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RequestedCompanySourceSeeder extends Seeder
{
    public static function sources(): array
    {
        return [
            ['name'=>'Bebo Technologies','website'=>'https://www.bebotechnologies.com','careers_url'=>'https://www.bebotechnologies.com/careers','country'=>'India','ats_provider'=>'bebo'],
            ['name'=>'Infosys','website'=>'https://www.infosys.com','careers_url'=>'https://digitalcareers.infosys.com/','country'=>'Global','ats_provider'=>'infosys_algolia'],
            ['name'=>'Wipro','website'=>'https://www.wipro.com','careers_url'=>'https://careers.wipro.com/','jobs_feed_url'=>'https://careers.wipro.com/search/?q=','country'=>'Global','ats_provider'=>'successfactors'],
            ['name'=>'HCLTech','website'=>'https://www.hcltech.com','careers_url'=>'https://careers.hcltech.com/','jobs_feed_url'=>'https://careers.hcltech.com/search/?q=','country'=>'Global','ats_provider'=>'successfactors'],
            ['name'=>'Capgemini','website'=>'https://www.capgemini.com','careers_url'=>'https://careers.capgemini.com/','jobs_feed_url'=>'https://careers.capgemini.com/search/?q=','country'=>'Global','ats_provider'=>'successfactors'],
            ['name'=>'Hexaware Technologies','website'=>'https://hexaware.com','careers_url'=>'https://fa-etqo-saasfaprod1.fa.ocs.oraclecloud.com/hcmUI/CandidateExperience/en/sites/CX_1','jobs_feed_url'=>'https://fa-etqo-saasfaprod1.fa.ocs.oraclecloud.com/hcmRestApi/resources/latest/recruitingCEJobRequisitions','ats_identifier'=>'CX_1','country'=>'Global','ats_provider'=>'oracle_recruiting'],
        ];
    }
    public function run(): void
    {
        foreach (self::sources() as $source) Company::updateOrCreate(['name'=>$source['name']], $source+[
            'slug'=>Str::slug($source['name']),'sync_enabled'=>true,'is_active'=>true,'industry'=>'IT Services','sector'=>'IT Services & Consulting',
        ]);
    }
}
