<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Seed technologies, job categories, and companies first
        $this->call([
            OwnerAccountSeeder::class,
            TechnologySeeder::class,
            JobCategorySeeder::class,
            JobTitleSeeder::class,
            CompanySeeder::class,
            OfficialCareerSourceSeeder::class,
            CompanyCategorySeeder::class,
            CompanyDiscoverySeeder::class,
            NewsIntelligenceSeeder::class,
            IndustryNewsCategorySeeder::class,
            InterviewCompanySeeder::class,
            IndustrialDirectorySeeder::class,
            IndustrialRecruitmentSeeder::class,
        ]);

        // Uncomment to create test users
        // \App\Models\User::factory(10)->create();
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
