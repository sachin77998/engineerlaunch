<?php

namespace Database\Seeders;

use App\Models\JobTitle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobTitleSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'Software Engineering' => [
                'Software Engineer', 'Senior Software Engineer', 'Junior Software Engineer',
                'Software Development Engineer (SDE)', 'SDE I', 'SDE II', 'SDE III',
                'Principal Software Engineer', 'Staff Software Engineer', 'Lead Software Engineer',
                'Frontend Developer', 'Backend Developer', 'Full Stack Developer', 'Mobile App Developer',
                'iOS Developer', 'Android Developer', 'Web Developer', 'PHP Developer', 'Laravel Developer',
                'Java Developer', 'Python Developer', '.NET Developer', 'React Developer',
                'Node.js Developer', 'WordPress Developer', 'Embedded Software Engineer', 'Game Developer',
                'Software Architect', 'Application Developer',
            ],
            'DevOps & Infrastructure' => [
                'DevOps Engineer', 'Senior DevOps Engineer', 'Site Reliability Engineer (SRE)',
                'Cloud Engineer', 'Cloud Architect', 'Infrastructure Engineer', 'Platform Engineer',
                'Systems Administrator', 'Network Engineer', 'Build & Release Engineer', 'Kubernetes Engineer',
            ],
            'Data' => [
                'Data Analyst', 'Data Scientist', 'Senior Data Scientist', 'Data Engineer',
                'Machine Learning Engineer', 'AI Engineer', 'Business Intelligence (BI) Developer',
                'Data Architect', 'Big Data Engineer', 'Analytics Engineer',
            ],
            'Quality Assurance' => [
                'QA Engineer', 'Senior QA Engineer', 'QA Automation Engineer', 'Manual Tester',
                'SDET (Software Development Engineer in Test)', 'Test Lead', 'Performance Test Engineer',
            ],
            'Security' => [
                'Security Engineer', 'Cybersecurity Analyst', 'Penetration Tester',
                'Information Security Analyst', 'Security Architect',
            ],
            'Product & Design' => [
                'Product Manager', 'Senior Product Manager', 'Associate Product Manager', 'Product Owner',
                'UX Designer', 'UI Designer', 'UI/UX Designer', 'Product Designer', 'Graphic Designer',
            ],
            'Management & Leadership' => [
                'Engineering Manager', 'Technical Lead', 'Team Lead', 'Project Manager',
                'Technical Project Manager', 'Delivery Manager', 'Scrum Master',
                'Chief Technology Officer (CTO)', 'VP of Engineering', 'Director of Engineering',
            ],
            'Support & Other' => [
                'Technical Support Engineer', 'IT Support Specialist', 'Solutions Architect',
                'Business Analyst', 'System Analyst', 'Database Administrator (DBA)',
                'Technical Writer', 'Sales Engineer', 'Implementation Engineer',
            ],
        ];

        $position = 0;
        foreach ($groups as $category => $titles) {
            foreach ($titles as $title) {
                JobTitle::withTrashed()->updateOrCreate(
                    ['title' => $title],
                    [
                        'category' => $category,
                        'slug' => Str::slug($title),
                        'is_active' => true,
                        'sort_order' => ++$position,
                        'deleted_at' => null,
                        'deleted_by' => null,
                    ]
                );
            }
        }
    }
}
