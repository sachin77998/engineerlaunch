<?php

namespace Database\Seeders;

use App\Models\Technology;
use Illuminate\Database\Seeder;

class TechnologySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $technologies = [
            // Backend Languages
            ['name' => 'PHP', 'category' => 'Backend', 'slug' => 'php'],
            ['name' => 'Python', 'category' => 'Backend', 'slug' => 'python'],
            ['name' => 'Java', 'category' => 'Backend', 'slug' => 'java'],
            ['name' => 'C++', 'category' => 'Backend', 'slug' => 'cpp'],
            ['name' => 'C#', 'category' => 'Backend', 'slug' => 'csharp'],
            ['name' => 'C', 'category' => 'Backend', 'slug' => 'c'],
            ['name' => 'Go', 'category' => 'Backend', 'slug' => 'go'],
            ['name' => 'Rust', 'category' => 'Backend', 'slug' => 'rust'],
            ['name' => 'Ruby', 'category' => 'Backend', 'slug' => 'ruby'],
            ['name' => 'TypeScript', 'category' => 'Backend', 'slug' => 'typescript'],
            ['name' => 'Node.js', 'category' => 'Backend', 'slug' => 'nodejs'],

            // Frontend
            ['name' => 'React', 'category' => 'Frontend', 'slug' => 'react'],
            ['name' => 'Vue.js', 'category' => 'Frontend', 'slug' => 'vuejs'],
            ['name' => 'Angular', 'category' => 'Frontend', 'slug' => 'angular'],
            ['name' => 'HTML5', 'category' => 'Frontend', 'slug' => 'html5'],
            ['name' => 'CSS3', 'category' => 'Frontend', 'slug' => 'css3'],
            ['name' => 'JavaScript', 'category' => 'Frontend', 'slug' => 'javascript'],
            ['name' => 'Svelte', 'category' => 'Frontend', 'slug' => 'svelte'],
            ['name' => 'Next.js', 'category' => 'Frontend', 'slug' => 'nextjs'],

            // Backend Frameworks
            ['name' => 'Laravel', 'category' => 'Backend', 'slug' => 'laravel'],
            ['name' => 'Spring Boot', 'category' => 'Backend', 'slug' => 'springboot'],
            ['name' => 'Django', 'category' => 'Backend', 'slug' => 'django'],
            ['name' => '.NET', 'category' => 'Backend', 'slug' => 'dotnet'],
            ['name' => 'Express.js', 'category' => 'Backend', 'slug' => 'expressjs'],
            ['name' => 'Rails', 'category' => 'Backend', 'slug' => 'rails'],
            ['name' => 'Flask', 'category' => 'Backend', 'slug' => 'flask'],

            // Databases
            ['name' => 'MySQL', 'category' => 'Database', 'slug' => 'mysql'],
            ['name' => 'PostgreSQL', 'category' => 'Database', 'slug' => 'postgresql'],
            ['name' => 'MongoDB', 'category' => 'Database', 'slug' => 'mongodb'],
            ['name' => 'Redis', 'category' => 'Database', 'slug' => 'redis'],
            ['name' => 'Elasticsearch', 'category' => 'Database', 'slug' => 'elasticsearch'],
            ['name' => 'Cassandra', 'category' => 'Database', 'slug' => 'cassandra'],
            ['name' => 'Oracle', 'category' => 'Database', 'slug' => 'oracle'],
            ['name' => 'SQL Server', 'category' => 'Database', 'slug' => 'sqlserver'],

            // Cloud & DevOps
            ['name' => 'AWS', 'category' => 'Cloud', 'slug' => 'aws'],
            ['name' => 'Azure', 'category' => 'Cloud', 'slug' => 'azure'],
            ['name' => 'Google Cloud', 'category' => 'Cloud', 'slug' => 'gcp'],
            ['name' => 'Docker', 'category' => 'DevOps', 'slug' => 'docker'],
            ['name' => 'Kubernetes', 'category' => 'DevOps', 'slug' => 'kubernetes'],
            ['name' => 'CI/CD', 'category' => 'DevOps', 'slug' => 'cicd'],
            ['name' => 'Jenkins', 'category' => 'DevOps', 'slug' => 'jenkins'],
            ['name' => 'Terraform', 'category' => 'DevOps', 'slug' => 'terraform'],
            ['name' => 'Ansible', 'category' => 'DevOps', 'slug' => 'ansible'],

            // ML & AI
            ['name' => 'Machine Learning', 'category' => 'ML/AI', 'slug' => 'machine-learning'],
            ['name' => 'Deep Learning', 'category' => 'ML/AI', 'slug' => 'deep-learning'],
            ['name' => 'AI Engineer', 'category' => 'ML/AI', 'slug' => 'ai-engineer'],
            ['name' => 'Data Science', 'category' => 'ML/AI', 'slug' => 'data-science'],
            ['name' => 'TensorFlow', 'category' => 'ML/AI', 'slug' => 'tensorflow'],
            ['name' => 'PyTorch', 'category' => 'ML/AI', 'slug' => 'pytorch'],
            ['name' => 'Scikit-learn', 'category' => 'ML/AI', 'slug' => 'scikitlearn'],
            ['name' => 'NLP', 'category' => 'ML/AI', 'slug' => 'nlp'],
            ['name' => 'Computer Vision', 'category' => 'ML/AI', 'slug' => 'computer-vision'],
            ['name' => 'OpenAI', 'category' => 'ML/AI', 'slug' => 'openai'],

            // Mobile
            ['name' => 'Android', 'category' => 'Mobile', 'slug' => 'android'],
            ['name' => 'iOS', 'category' => 'Mobile', 'slug' => 'ios'],
            ['name' => 'React Native', 'category' => 'Mobile', 'slug' => 'react-native'],
            ['name' => 'Flutter', 'category' => 'Mobile', 'slug' => 'flutter'],
            ['name' => 'Swift', 'category' => 'Mobile', 'slug' => 'swift'],
            ['name' => 'Kotlin', 'category' => 'Mobile', 'slug' => 'kotlin'],

            // Other Technologies
            ['name' => 'GraphQL', 'category' => 'Backend', 'slug' => 'graphql'],
            ['name' => 'REST API', 'category' => 'Backend', 'slug' => 'rest-api'],
            ['name' => 'Microservices', 'category' => 'Architecture', 'slug' => 'microservices'],
            ['name' => 'System Design', 'category' => 'Architecture', 'slug' => 'system-design'],
            ['name' => 'Git', 'category' => 'Tools', 'slug' => 'git'],
            ['name' => 'Linux', 'category' => 'Operating System', 'slug' => 'linux'],
            ['name' => 'Windows Server', 'category' => 'Operating System', 'slug' => 'windows-server'],
            ['name' => 'Agile/Scrum', 'category' => 'Methodology', 'slug' => 'agile-scrum'],
            ['name' => 'JIRA', 'category' => 'Tools', 'slug' => 'jira'],
            ['name' => 'Apache', 'category' => 'Tools', 'slug' => 'apache'],
            ['name' => 'Nginx', 'category' => 'Tools', 'slug' => 'nginx'],
        ];

        foreach ($technologies as $tech) {
            Technology::firstOrCreate(
                ['name' => $tech['name']],
                [
                    'slug' => $tech['slug'],
                    'category' => $tech['category'],
                ]
            );
        }

        $this->command->info('Technologies seeded successfully!');
    }
}
