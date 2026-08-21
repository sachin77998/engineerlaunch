<?php

namespace Database\Seeders;

use App\Models\JobCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Software Developer', 'description' => 'Entry to mid-level software development roles'],
            ['name' => 'Software Developer 2', 'description' => 'Mid-level software development roles'],
            ['name' => 'Software Developer 3', 'description' => 'Senior software development roles'],
            ['name' => 'Senior Software Engineer', 'description' => 'Senior software engineering positions'],
            ['name' => 'Staff Engineer', 'description' => 'Staff engineer roles'],
            ['name' => 'Principal Engineer', 'description' => 'Principal engineer positions'],
            ['name' => 'Product Manager', 'description' => 'Product management roles'],
            ['name' => 'Senior Product Manager', 'description' => 'Senior product management roles'],
            ['name' => 'Scrum Master', 'description' => 'Agile/Scrum master roles'],
            ['name' => 'Technical Lead', 'description' => 'Technical leadership roles'],
            ['name' => 'Engineering Manager', 'description' => 'Engineering management positions'],
            ['name' => 'Data Engineer', 'description' => 'Data engineering roles'],
            ['name' => 'Data Scientist', 'description' => 'Data science roles'],
            ['name' => 'ML Engineer', 'description' => 'Machine learning engineer roles'],
            ['name' => 'AI Engineer', 'description' => 'Artificial intelligence engineer roles'],
            ['name' => 'DevOps Engineer', 'description' => 'DevOps engineering roles'],
            ['name' => 'Cloud Architect', 'description' => 'Cloud architecture roles'],
            ['name' => 'Security Engineer', 'description' => 'Cybersecurity engineering roles'],
            ['name' => 'Frontend Developer', 'description' => 'Frontend development roles'],
            ['name' => 'Backend Developer', 'description' => 'Backend development roles'],
            ['name' => 'Full Stack Developer', 'description' => 'Full stack development roles'],
            ['name' => 'Mobile Developer', 'description' => 'Mobile development roles'],
            ['name' => 'QA Engineer', 'description' => 'Quality assurance roles'],
            ['name' => 'Solutions Architect', 'description' => 'Solutions architecture roles'],
            ['name' => 'Systems Architect', 'description' => 'Systems architecture roles'],
            ['name' => 'Internship', 'description' => 'Internship positions'],
            ['name' => 'Graduate Program', 'description' => 'Graduate and fresher programs'],
        ];

        foreach ($categories as $category) {
            JobCategory::firstOrCreate(
                ['name' => $category['name']],
                [
                    'slug' => Str::slug($category['name']),
                    'description' => $category['description'],
                ]
            );
        }

        $this->command->info('Job categories seeded successfully!');
    }
}
