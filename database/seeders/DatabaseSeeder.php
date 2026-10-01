<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OwnerAccountSeeder::class,
            TechnologySeeder::class,
            JobCategorySeeder::class,
            JobTitleSeeder::class,
            CompanySeeder::class,
            RequestedEmployerInventorySeeder::class,
            OfficialCareerSourceSeeder::class,
            RequestedCompanySourceSeeder::class,
            CyberCityCompanySeeder::class,
            CareerExplorerSeeder::class,
            CompanyCategorySeeder::class,
            CompanyDiscoverySeeder::class,
            NewsIntelligenceSeeder::class,
            IndustryNewsCategorySeeder::class,
            InterviewCompanySeeder::class,
            IndustrialDirectorySeeder::class,
            IndustrialRecruitmentSeeder::class,
        ]);
    }
}
