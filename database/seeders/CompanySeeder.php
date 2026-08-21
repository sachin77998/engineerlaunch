<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            // Big Tech & Software
            ['name' => 'Microsoft', 'website' => 'https://microsoft.com', 'careers_url' => 'https://careers.microsoft.com', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Big Tech'],
            ['name' => 'Google', 'website' => 'https://google.com', 'careers_url' => 'https://careers.google.com', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Big Tech'],
            ['name' => 'Amazon', 'website' => 'https://amazon.com', 'careers_url' => 'https://amazon.jobs', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Big Tech'],
            ['name' => 'Meta', 'website' => 'https://meta.com', 'careers_url' => 'https://www.metacareers.com', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Big Tech'],
            ['name' => 'Apple', 'website' => 'https://apple.com', 'careers_url' => 'https://www.apple.com/careers', 'country' => 'USA', 'industry' => 'Hardware', 'sector' => 'Big Tech'],

            // Enterprise Software
            ['name' => 'Oracle', 'website' => 'https://oracle.com', 'careers_url' => 'https://www.oracle.com/careers', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Enterprise Software'],
            ['name' => 'Salesforce', 'website' => 'https://salesforce.com', 'careers_url' => 'https://www.salesforce.com/company/careers', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Enterprise Software'],
            ['name' => 'SAP', 'website' => 'https://sap.com', 'careers_url' => 'https://www.sap.com/careers', 'country' => 'Germany', 'industry' => 'Software', 'sector' => 'Enterprise Software'],
            ['name' => 'Adobe', 'website' => 'https://adobe.com', 'careers_url' => 'https://careers.adobe.com', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Enterprise Software'],
            ['name' => 'ServiceNow', 'website' => 'https://servicenow.com', 'careers_url' => 'https://careers.servicenow.com', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Enterprise Software'],

            // IT Services & Consulting
            ['name' => 'TCS', 'website' => 'https://tcs.com', 'careers_url' => 'https://careers.tcs.com', 'country' => 'India', 'industry' => 'IT Services', 'sector' => 'IT Services & Consulting'],
            ['name' => 'Infosys', 'website' => 'https://infosys.com', 'careers_url' => 'https://www.infosys.com/careers', 'country' => 'India', 'industry' => 'IT Services', 'sector' => 'IT Services & Consulting'],
            ['name' => 'Wipro', 'website' => 'https://wipro.com', 'careers_url' => 'https://careers.wipro.com', 'country' => 'India', 'industry' => 'IT Services', 'sector' => 'IT Services & Consulting', 'ats_provider' => 'successfactors', 'jobs_feed_url' => 'https://careers.wipro.com/viewalljobs/', 'sync_enabled' => false],
            ['name' => 'Cognizant', 'website' => 'https://cognizant.com', 'careers_url' => 'https://www.cognizant.com/careers', 'country' => 'USA', 'industry' => 'IT Services', 'sector' => 'IT Services & Consulting'],
            ['name' => 'Accenture', 'website' => 'https://accenture.com', 'careers_url' => 'https://www.accenture.com/in-en/careers/jobsearch', 'country' => 'Global', 'industry' => 'IT Services', 'sector' => 'IT Services & Consulting', 'ats_provider' => 'workday', 'ats_identifier' => 'accenture/AccentureCareers', 'jobs_feed_url' => 'https://accenture.wd103.myworkdayjobs.com/wday/cxs/accenture/AccentureCareers/jobs', 'sync_enabled' => true],
            ['name' => 'IBM', 'website' => 'https://ibm.com', 'careers_url' => 'https://www.ibm.com/careers', 'country' => 'USA', 'industry' => 'IT Services', 'sector' => 'IT Services & Consulting'],
            ['name' => 'Capgemini', 'website' => 'https://capgemini.com', 'careers_url' => 'https://www.capgemini.com/careers', 'country' => 'France', 'industry' => 'IT Services', 'sector' => 'IT Services & Consulting'],
            ['name' => 'HCLTech', 'website' => 'https://hcltech.com', 'careers_url' => 'https://www.hcltech.com/careers', 'country' => 'India', 'industry' => 'IT Services', 'sector' => 'IT Services & Consulting'],
            ['name' => 'Tech Mahindra', 'website' => 'https://techmahindra.com', 'careers_url' => 'https://www.techmahindra.com/en-us/careers', 'country' => 'India', 'industry' => 'IT Services', 'sector' => 'IT Services & Consulting'],
            ['name' => 'Amdocs', 'website' => 'https://amdocs.com', 'careers_url' => 'https://www.amdocs.com/careers', 'country' => 'Israel', 'industry' => 'IT Services', 'sector' => 'IT Services & Consulting'],

            // Cybersecurity
            ['name' => 'Palo Alto Networks', 'website' => 'https://paloaltonetworks.com', 'careers_url' => 'https://www.paloaltonetworks.com/careers', 'country' => 'USA', 'industry' => 'Cybersecurity', 'sector' => 'Cybersecurity'],
            ['name' => 'CrowdStrike', 'website' => 'https://crowdstrike.com', 'careers_url' => 'https://www.crowdstrike.com/careers', 'country' => 'USA', 'industry' => 'Cybersecurity', 'sector' => 'Cybersecurity'],
            ['name' => 'Fortinet', 'website' => 'https://fortinet.com', 'careers_url' => 'https://www.fortinet.com/careers', 'country' => 'USA', 'industry' => 'Cybersecurity', 'sector' => 'Cybersecurity'],
            ['name' => 'Zscaler', 'website' => 'https://zscaler.com', 'careers_url' => 'https://www.zscaler.com/company/careers', 'country' => 'USA', 'industry' => 'Cybersecurity', 'sector' => 'Cybersecurity'],
            ['name' => 'Cloudflare', 'website' => 'https://cloudflare.com', 'careers_url' => 'https://www.cloudflare.com/careers', 'country' => 'USA', 'industry' => 'Cybersecurity', 'sector' => 'Cybersecurity'],

            // E-commerce & Startups (India)
            ['name' => 'Flipkart', 'website' => 'https://flipkart.com', 'careers_url' => 'https://www.flipkart.com/careers', 'country' => 'India', 'industry' => 'E-commerce', 'sector' => 'E-commerce'],
            ['name' => 'Myntra', 'website' => 'https://myntra.com', 'careers_url' => 'https://www.myntra.com/jobs', 'country' => 'India', 'industry' => 'E-commerce', 'sector' => 'E-commerce'],
            ['name' => 'Zomato', 'website' => 'https://zomato.com', 'careers_url' => 'https://www.zomato.com/careers', 'country' => 'India', 'industry' => 'Food Delivery', 'sector' => 'E-commerce'],
            ['name' => 'Blinkit', 'website' => 'https://blinkit.com', 'careers_url' => 'https://www.blinkit.com/careers', 'country' => 'India', 'industry' => 'Quick Commerce', 'sector' => 'E-commerce'],
            ['name' => 'Swiggy', 'website' => 'https://swiggy.com', 'careers_url' => 'https://www.swiggy.com/careers', 'country' => 'India', 'industry' => 'Food Delivery', 'sector' => 'E-commerce'],

            // Hardware & Infrastructure
            ['name' => 'Cisco Systems', 'website' => 'https://cisco.com', 'careers_url' => 'https://www.cisco.com/c/en/us/about/careers', 'country' => 'USA', 'industry' => 'Hardware', 'sector' => 'Hardware & Infrastructure'],
            ['name' => 'Dell Technologies', 'website' => 'https://dell.com', 'careers_url' => 'https://www.dell.com/en-us/careers', 'country' => 'USA', 'industry' => 'Hardware', 'sector' => 'Hardware & Infrastructure'],
            ['name' => 'HP Inc', 'website' => 'https://hp.com', 'careers_url' => 'https://www.hp.com/us-en/hp-jobs', 'country' => 'USA', 'industry' => 'Hardware', 'sector' => 'Hardware & Infrastructure'],

            // Semiconductors
            ['name' => 'NVIDIA', 'website' => 'https://nvidia.com', 'careers_url' => 'https://www.nvidia.com/en-us/about-nvidia/careers', 'country' => 'USA', 'industry' => 'Semiconductors', 'sector' => 'Semiconductors'],
            ['name' => 'Intel', 'website' => 'https://intel.com', 'careers_url' => 'https://www.intel.com/content/www/us/en/careers/careers-home.html', 'country' => 'USA', 'industry' => 'Semiconductors', 'sector' => 'Semiconductors'],
            ['name' => 'AMD', 'website' => 'https://amd.com', 'careers_url' => 'https://www.amd.com/en/careers', 'country' => 'USA', 'industry' => 'Semiconductors', 'sector' => 'Semiconductors'],
            ['name' => 'Qualcomm', 'website' => 'https://qualcomm.com', 'careers_url' => 'https://www.qualcomm.com/careers', 'country' => 'USA', 'industry' => 'Semiconductors', 'sector' => 'Semiconductors'],

            // Banking & Finance
            ['name' => 'Bank of America', 'website' => 'https://bofa.com', 'careers_url' => 'https://www.bofa.com/en/careers', 'country' => 'USA', 'industry' => 'Banking', 'sector' => 'Banking & Finance'],
            ['name' => 'Deutsche Bank', 'website' => 'https://db.com', 'careers_url' => 'https://www.deutschebank.com/careers', 'country' => 'Germany', 'industry' => 'Banking', 'sector' => 'Banking & Finance'],
            ['name' => 'HSBC', 'website' => 'https://hsbc.com', 'careers_url' => 'https://www.hsbc.com/careers', 'country' => 'UK', 'industry' => 'Banking', 'sector' => 'Banking & Finance'],
            ['name' => 'Citi', 'website' => 'https://citigroup.com', 'careers_url' => 'https://www.citigroup.com/careers', 'country' => 'USA', 'industry' => 'Banking', 'sector' => 'Banking & Finance'],
            ['name' => 'Standard Chartered', 'website' => 'https://sc.com', 'careers_url' => 'https://www.sc.com/careers', 'country' => 'UK', 'industry' => 'Banking', 'sector' => 'Banking & Finance'],
            ['name' => 'SoftBank', 'website' => 'https://softbank.jp', 'careers_url' => 'https://www.softbank.jp/en/careers', 'country' => 'Japan', 'industry' => 'Banking', 'sector' => 'Banking & Finance'],

            // Additional Tech Companies
            ['name' => 'Workday', 'website' => 'https://workday.com', 'careers_url' => 'https://www.workday.com/careers', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Enterprise Software'],
            ['name' => 'VMware', 'website' => 'https://vmware.com', 'careers_url' => 'https://www.vmware.com/careers', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Enterprise Software'],
            ['name' => 'Snowflake', 'website' => 'https://snowflake.com', 'careers_url' => 'https://www.snowflake.com/careers', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Enterprise Software'],
            ['name' => 'MongoDB', 'website' => 'https://mongodb.com', 'careers_url' => 'https://www.mongodb.com/careers', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Enterprise Software'],
            ['name' => 'Datadog', 'website' => 'https://datadoghq.com', 'careers_url' => 'https://www.datadoghq.com/careers', 'country' => 'USA', 'industry' => 'Software', 'sector' => 'Enterprise Software'],
        ];

        foreach ($companies as $companyData) {
            Company::updateOrCreate(
                ['name' => $companyData['name']],
                [
                    'slug' => Str::slug($companyData['name']),
                    'website' => $companyData['website'],
                    'careers_url' => $companyData['careers_url'],
                    'country' => $companyData['country'],
                    'industry' => $companyData['industry'],
                    'sector' => $companyData['sector'],
                    'ats_provider' => $companyData['ats_provider'] ?? null,
                    'ats_identifier' => $companyData['ats_identifier'] ?? null,
                    'jobs_feed_url' => $companyData['jobs_feed_url'] ?? null,
                    'sync_enabled' => $companyData['sync_enabled'] ?? false,
                    'is_active' => true,
                ]
            );
        }

        $verifiedFeeds = [
            'Amazon' => ['amazon', 'amazon-jobs', 'https://www.amazon.jobs/en/search.json'],
            'AMD' => ['icims_jibe', 'amd', 'https://careers.amd.com/api/jobs'],
            'Adobe' => ['workday', 'adobe/external_experienced', 'https://adobe.wd5.myworkdayjobs.com/wday/cxs/adobe/external_experienced/jobs'],
            'ServiceNow' => ['smartrecruiters', 'ServiceNow', null],
            'CrowdStrike' => ['workday', 'crowdstrike/crowdstrikecareers', 'https://crowdstrike.wd5.myworkdayjobs.com/wday/cxs/crowdstrike/crowdstrikecareers/jobs'],
            'Zscaler' => ['greenhouse', 'zscaler', null],
            'Cloudflare' => ['greenhouse', 'cloudflare', null],
            'Intel' => ['workday', 'intel/External', 'https://intel.wd1.myworkdayjobs.com/wday/cxs/intel/External/jobs'],
            'NVIDIA' => ['workday', 'nvidia/NVIDIAExternalCareerSite', 'https://nvidia.wd5.myworkdayjobs.com/wday/cxs/nvidia/NVIDIAExternalCareerSite/jobs'],
            'MongoDB' => ['greenhouse', 'mongodb', null],
            'Datadog' => ['greenhouse', 'datadog', null],
        ];
        foreach ($verifiedFeeds as $name => [$provider, $identifier, $feedUrl]) {
            Company::where('name', $name)->update([
                'ats_provider' => $provider,
                'ats_identifier' => $identifier,
                'jobs_feed_url' => $feedUrl,
                'sync_enabled' => true,
            ]);
        }

        $additionalFeeds = [
            ['Hewlett Packard Enterprise (HPE)', 'https://hpe.com', 'https://careers.hpe.com', 'Hardware', 'Hardware & Infrastructure', 'workday', 'hpe/Jobsathpe', 'https://hpe.wd5.myworkdayjobs.com/wday/cxs/hpe/Jobsathpe/jobs'],
            ['Broadcom', 'https://broadcom.com', 'https://www.broadcom.com/company/careers', 'Semiconductors', 'Semiconductors', 'workday', 'broadcom/External_Career', 'https://broadcom.wd1.myworkdayjobs.com/wday/cxs/broadcom/External_Career/jobs'],
            ['Arista Networks', 'https://arista.com', 'https://www.arista.com/en/careers', 'Networking', 'Hardware & Infrastructure', 'smartrecruiters', 'AristaNetworks', null],
        ];
        foreach ($additionalFeeds as [$name, $website, $careers, $industry, $sector, $provider, $identifier, $feedUrl]) {
            Company::updateOrCreate(['name' => $name], [
                'slug' => Str::slug($name), 'website' => $website, 'careers_url' => $careers,
                'country' => 'USA', 'industry' => $industry, 'sector' => $sector,
                'ats_provider' => $provider, 'ats_identifier' => $identifier,
                'jobs_feed_url' => $feedUrl, 'sync_enabled' => true, 'is_active' => true,
            ]);
        }

        // Requested companies without a verified adapter remain visible but
        // unscheduled until their official feed is validated.
        $requestedCompanies = [
            ['NTT DATA', 'https://nttdata.com', 'https://careers.nttdata.com', 'Japan', 'IT Services', 'IT Services & Consulting'],
            ['DXC Technology', 'https://dxc.com', 'https://careers.dxc.com', 'USA', 'IT Services', 'IT Services & Consulting'],
            ['LTIMindtree', 'https://ltimindtree.com', 'https://www.ltimindtree.com/careers/', 'India', 'IT Services', 'IT Services & Consulting'],
            ['Atos', 'https://atos.net', 'https://atos.net/en/careers', 'France', 'IT Services', 'IT Services & Consulting'],
            ['CGI', 'https://cgi.com', 'https://www.cgi.com/en/careers', 'Canada', 'IT Services', 'IT Services & Consulting'],
            ['Kyndryl', 'https://kyndryl.com', 'https://www.kyndryl.com/us/en/careers', 'USA', 'IT Services', 'IT Services & Consulting'],
            ['Tencent', 'https://tencent.com', 'https://careers.tencent.com', 'China', 'Technology', 'Big Tech'],
            ['Alibaba', 'https://alibabagroup.com', 'https://talent.alibaba.com', 'China', 'Technology', 'Big Tech'],
            ['Baidu', 'https://baidu.com', 'https://talent.baidu.com', 'China', 'Technology', 'Big Tech'],
            ['Lenovo', 'https://lenovo.com', 'https://jobs.lenovo.com', 'China', 'Hardware', 'Hardware & Infrastructure'],
            ['Juniper Networks', 'https://juniper.net', 'https://careers.juniper.net', 'USA', 'Networking', 'Hardware & Infrastructure'],
            ['TSMC', 'https://tsmc.com', 'https://careers.tsmc.com', 'Taiwan', 'Semiconductors', 'Semiconductors'],
            ['ASML', 'https://asml.com', 'https://www.asml.com/en/careers', 'Netherlands', 'Semiconductors', 'Semiconductors'],
        ];
        foreach ($requestedCompanies as [$name, $website, $careers, $country, $industry, $sector]) {
            Company::firstOrCreate(['name' => $name], [
                'slug' => Str::slug($name), 'website' => $website, 'careers_url' => $careers,
                'country' => $country, 'industry' => $industry, 'sector' => $sector, 'is_active' => true,
            ]);
        }

        $financeAndLearningCompanies = [
            ['Groww', 'https://groww.in', 'https://groww.in/careers', 'India', 'Financial Technology'],
            ['Zerodha (Kite)', 'https://zerodha.com', 'https://zerodha.com/careers/', 'India', 'Financial Technology'],
            ['GeeksforGeeks', 'https://geeksforgeeks.org', 'https://www.geeksforgeeks.org/careers/', 'India', 'Education Technology'],
            ['S&P Global', 'https://spglobal.com', 'https://careers.spglobal.com', 'USA', 'Financial Data'],
            ['LinkedIn', 'https://linkedin.com', 'https://careers.linkedin.com', 'USA', 'Professional Network'],
        ];
        foreach ($financeAndLearningCompanies as [$name, $website, $careers, $country, $industry]) {
            Company::firstOrCreate(['name' => $name], [
                'slug' => Str::slug($name), 'website' => $website, 'careers_url' => $careers,
                'country' => $country, 'industry' => $industry, 'sector' => 'Technology', 'is_active' => true,
            ]);
        }

        $this->command->info('Companies seeded successfully!');
    }
}
