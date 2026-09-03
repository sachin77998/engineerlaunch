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
                    'ats_provider' => $source['ats_provider'],
                    'ats_identifier' => $source['ats_identifier'],
                    'jobs_feed_url' => null,
                    'sync_enabled' => true,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info(count(self::sources()).' verified official career sources seeded.');
    }
}
