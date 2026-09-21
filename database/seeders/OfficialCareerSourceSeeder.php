<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OfficialCareerSourceSeeder extends Seeder
{
    /**
     * Official, publicly accessible ATS feeds verified to return live jobs.
     *
     * Keep source facts here instead of seeding copied job descriptions. The
     * ingestion service owns normalization, duplicate detection and expiry.
     *
     * @return array<int, array<string, string>>
     */
    public static function sources(): array
    {
        return [
            [
                'name' => 'GitLab',
                'website' => 'https://about.gitlab.com',
                'careers_url' => 'https://about.gitlab.com/jobs/all-jobs/',
                'country' => 'Global',
                'industry' => 'Software Product',
                'sector' => 'Enterprise Software',
                'ats_provider' => 'greenhouse',
                'ats_identifier' => 'gitlab',
            ],
            [
                'name' => 'HubSpot',
                'website' => 'https://www.hubspot.com',
                'careers_url' => 'https://www.hubspot.com/careers/jobs',
                'country' => 'USA',
                'industry' => 'Software Product',
                'sector' => 'Enterprise Software',
                'ats_provider' => 'greenhouse',
                'ats_identifier' => 'hubspotjobs',
            ],
            [
                'name' => 'Twilio',
                'website' => 'https://www.twilio.com',
                'careers_url' => 'https://www.twilio.com/en-us/company/jobs',
                'country' => 'USA',
                'industry' => 'Cloud Communications',
                'sector' => 'Enterprise Software',
                'ats_provider' => 'greenhouse',
                'ats_identifier' => 'twilio',
            ],
            [
                'name' => 'Figma',
                'website' => 'https://www.figma.com',
                'careers_url' => 'https://www.figma.com/careers/',
                'country' => 'USA',
                'industry' => 'Design Software',
                'sector' => 'Software Product',
                'ats_provider' => 'greenhouse',
                'ats_identifier' => 'figma',
            ],
            [
                'name' => 'Elastic',
                'website' => 'https://www.elastic.co',
                'careers_url' => 'https://www.elastic.co/careers/',
                'country' => 'Global',
                'industry' => 'Data Infrastructure',
                'sector' => 'Enterprise Software',
                'ats_provider' => 'greenhouse',
                'ats_identifier' => 'elastic',
            ],
            [
                'name' => 'Okta',
                'website' => 'https://www.okta.com',
                'careers_url' => 'https://www.okta.com/company/careers/',
                'country' => 'USA',
                'industry' => 'Cybersecurity',
                'sector' => 'Cybersecurity',
                'ats_provider' => 'greenhouse',
                'ats_identifier' => 'okta',
            ],
            [
                'name' => 'Stripe',
                'website' => 'https://stripe.com',
                'careers_url' => 'https://stripe.com/jobs/search',
                'country' => 'Global',
                'industry' => 'FinTech / Payments',
                'sector' => 'Banking & Finance',
                'ats_provider' => 'greenhouse',
                'ats_identifier' => 'stripe',
            ],
            [
                'name' => 'Databricks',
                'website' => 'https://www.databricks.com',
                'careers_url' => 'https://www.databricks.com/company/careers/open-positions',
                'country' => 'USA',
                'industry' => 'Data & AI',
                'sector' => 'Enterprise Software',
                'ats_provider' => 'greenhouse',
                'ats_identifier' => 'databricks',
            ],
            [
                'name' => 'Postman', 'website' => 'https://www.postman.com',
                'careers_url' => 'https://www.postman.com/company/careers/', 'country' => 'Global',
                'industry' => 'Developer Tools', 'sector' => 'Software Product',
                'ats_provider' => 'greenhouse', 'ats_identifier' => 'postman',
            ],
            [
                'name' => 'Rubrik', 'website' => 'https://www.rubrik.com',
                'careers_url' => 'https://www.rubrik.com/company/careers', 'country' => 'Global',
                'industry' => 'Cybersecurity', 'sector' => 'Enterprise Software',
                'ats_provider' => 'greenhouse', 'ats_identifier' => 'rubrik',
            ],
            [
                'name' => 'Cisco', 'website' => 'https://www.cisco.com',
                'careers_url' => 'https://jobs.cisco.com/', 'country' => 'Global',
                'industry' => 'Networking', 'sector' => 'Technology',
                'ats_provider' => 'workday', 'ats_identifier' => 'cisco/Cisco_Careers',
                'jobs_feed_url' => 'https://cisco.wd5.myworkdayjobs.com/wday/cxs/cisco/Cisco_Careers/jobs',
            ],
            [
                'name' => 'NVIDIA', 'website' => 'https://www.nvidia.com',
                'careers_url' => 'https://www.nvidia.com/en-us/about-nvidia/careers/', 'country' => 'Global',
                'industry' => 'Semiconductors & AI', 'sector' => 'Technology',
                'ats_provider' => 'workday', 'ats_identifier' => 'nvidia/NVIDIAExternalCareerSite',
                'jobs_feed_url' => 'https://nvidia.wd5.myworkdayjobs.com/wday/cxs/nvidia/NVIDIAExternalCareerSite/jobs',
            ],
            [
                'name' => 'Salesforce', 'website' => 'https://www.salesforce.com',
                'careers_url' => 'https://careers.salesforce.com/en/jobs/', 'country' => 'Global',
                'industry' => 'Enterprise Software', 'sector' => 'Technology',
                'ats_provider' => 'workday', 'ats_identifier' => 'salesforce/External_Career_Site',
                'jobs_feed_url' => 'https://salesforce.wd12.myworkdayjobs.com/wday/cxs/salesforce/External_Career_Site/jobs',
            ],
            [
                'name' => 'Adobe', 'website' => 'https://www.adobe.com',
                'careers_url' => 'https://careers.adobe.com/us/en', 'country' => 'Global',
                'industry' => 'Software Product', 'sector' => 'Technology',
                'ats_provider' => 'workday', 'ats_identifier' => 'adobe/external_experienced',
                'jobs_feed_url' => 'https://adobe.wd5.myworkdayjobs.com/wday/cxs/adobe/external_experienced/jobs',
            ],
            [
                'name' => 'Hewlett Packard Enterprise', 'website' => 'https://www.hpe.com',
                'careers_url' => 'https://careers.hpe.com/us/en', 'country' => 'Global',
                'industry' => 'Enterprise Technology', 'sector' => 'Technology',
                'ats_provider' => 'workday', 'ats_identifier' => 'hpe/Jobsathpe',
                'jobs_feed_url' => 'https://hpe.wd5.myworkdayjobs.com/wday/cxs/hpe/Jobsathpe/jobs',
            ],
            [
                'name' => 'Caterpillar Inc', 'website' => 'https://www.caterpillar.com',
                'careers_url' => 'https://careers.caterpillar.com/', 'country' => 'Global',
                'industry' => 'Heavy Engineering', 'sector' => 'Manufacturing',
                'ats_provider' => 'workday', 'ats_identifier' => 'cat/CaterpillarCareers',
                'jobs_feed_url' => 'https://cat.wd5.myworkdayjobs.com/wday/cxs/cat/CaterpillarCareers/jobs',
            ],
            [
                'name' => 'HCLTech', 'website' => 'https://www.hcltech.com',
                'careers_url' => 'https://careers.hcltech.com/', 'country' => 'India',
                'industry' => 'IT Services', 'sector' => 'IT Services & Consulting',
                'ats_provider' => 'successfactors',
                'jobs_feed_url' => 'https://careers.hcltech.com/search/?q=&sortColumn=referencedate&sortDirection=desc',
                'sync_enabled' => false,
            ],
            [
                'name' => 'Capgemini', 'website' => 'https://www.capgemini.com',
                'careers_url' => 'https://jobs.capgemini.com/', 'country' => 'France',
                'industry' => 'IT Services', 'sector' => 'IT Services & Consulting',
                'ats_provider' => 'successfactors',
                'jobs_feed_url' => 'https://jobs.capgemini.com/search/?q=&sortColumn=referencedate&sortDirection=desc',
                'sync_enabled' => false,
            ],
            [
                'name' => 'Amdocs', 'website' => 'https://www.amdocs.com',
                'careers_url' => 'https://jobs.amdocs.com/', 'country' => 'Israel',
                'industry' => 'IT Services', 'sector' => 'IT Services & Consulting',
                'ats_provider' => 'successfactors',
                'jobs_feed_url' => 'https://jobs.amdocs.com/search/?q=&sortColumn=referencedate&sortDirection=desc',
                'sync_enabled' => false,
            ],
            [
                'name' => 'Infosys', 'website' => 'https://www.infosys.com',
                'careers_url' => 'https://digitalcareers.infosys.com/', 'country' => 'India',
                'industry' => 'IT Services', 'sector' => 'IT Services & Consulting',
            ],
            [
                'name' => 'TCS', 'website' => 'https://www.tcs.com',
                'careers_url' => 'https://ibegin.tcsapps.com/candidate/', 'country' => 'India',
                'industry' => 'IT Services', 'sector' => 'IT Services & Consulting',
            ],
            [
                'name' => 'Hexaware Technologies', 'website' => 'https://hexaware.com',
                'careers_url' => 'https://hexaware.com/careers/', 'country' => 'India',
                'industry' => 'IT Services', 'sector' => 'IT Services & Consulting',
            ],
            [
                'name' => 'Cognizant', 'website' => 'https://www.cognizant.com',
                'careers_url' => 'https://careers.cognizant.com/india-en/jobs/', 'country' => 'USA',
                'industry' => 'IT Services', 'sector' => 'IT Services & Consulting',
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::sources() as $source) {
            Company::updateOrCreate(
                ['name' => $source['name']],
                [
                    'slug' => Str::slug($source['name']),
                    'website' => $source['website'],
                    'careers_url' => $source['careers_url'],
                    'country' => $source['country'],
                    'industry' => $source['industry'],
                    'sector' => $source['sector'],
                    'ats_provider' => $source['ats_provider'] ?? null,
                    'ats_identifier' => $source['ats_identifier'] ?? null,
                    'jobs_feed_url' => $source['jobs_feed_url'] ?? null,
                    'sync_enabled' => $source['sync_enabled'] ?? isset($source['ats_provider']),
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info(count(self::sources()).' verified official career sources seeded.');
    }
}
