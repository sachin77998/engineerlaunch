<?php

namespace Database\Seeders;

use App\Models\NewsArticle;
use App\Models\NewsCategory;
use App\Models\NewsSource;
use Illuminate\Database\Seeder;

class MechanicalIndustryNewsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Mechanical Engineering', 'mechanical-engineering', '⚙️'],
            ['Manufacturing', 'manufacturing', '🏭'],
            ['Automobile', 'automobile', '🚗'],
            ['Tractors & Agriculture', 'tractors-agriculture', '🚜'],
            ['Forging', 'forging', '🔩'],
            ['Steel & Metals', 'steel-metals', '🏗️'],
            ['EV & Battery', 'electric-vehicles', '🔋'],
            ['Industrial Automation', 'industrial-automation', '🤖'],
            ['Robotics', 'robotics', '🦾'],
            ['CAD / CAE', 'cad-cae', '📐'],
        ];

        foreach ($categories as $index => [$name, $slug, $icon]) {
            NewsCategory::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'icon' => $icon, 'color' => '#1769e0', 'is_active' => true, 'sort_order' => 20 + $index]
            );
        }

        $sources = collect([
            'Mahindra' => 'official',
            'TAFE' => 'official',
            'Mechanical Industry Desk' => 'editorial',
        ])->mapWithKeys(fn (string $type, string $name) => [
            $name => NewsSource::updateOrCreate(
                ['name' => $name],
                ['website' => config('app.url'), 'source_type' => $type, 'is_active' => true]
            ),
        ]);

        $articles = [
            [
                'category' => 'manufacturing', 'source' => 'Mahindra',
                'title' => 'Mahindra moves ahead with Nagpur mega manufacturing project and plans a dedicated mechanical-skilling centre',
                'slug' => 'mahindra-nagpur-manufacturing-skilling-centre-mechanical-engineering',
                'excerpt' => 'Mahindra is moving toward an integrated automobile and tractor manufacturing facility in Nagpur while preparing talent in mechanical operations, welding and painting.',
                'summary' => 'The proposed Nagpur manufacturing project combines automobile and tractor production, supplier localisation, automation and workforce development through a dedicated technical-skilling centre.',
                'company_name' => 'Mahindra & Mahindra', 'source_published_at' => '2026-09-07', 'relevance_score' => 98,
                'skills' => ['Mechanical Engineering', 'Manufacturing', 'Welding', 'Painting', 'Automation', 'Robotics', 'Quality Control', 'CAD/CAM'],
                'roles' => ['Mechanical Engineer', 'Production Engineer', 'Manufacturing Engineer', 'Quality Engineer', 'Welding Engineer'],
                'locations' => ['Nagpur', 'Maharashtra'], 'industries' => ['Automobile', 'Tractor Manufacturing'],
                'career_impact' => 'The project can expand demand for production, quality, maintenance, welding, tooling and industrial-engineering talent.',
            ],
            [
                'category' => 'tractors-agriculture', 'source' => 'TAFE',
                'title' => 'TAFE plans a new North India tractor manufacturing plant as farm mechanisation demand grows',
                'slug' => 'tafe-north-india-tractor-manufacturing-plant',
                'excerpt' => 'TAFE is planning a fifth manufacturing facility in North India with approximately 60,000 tractors of annual capacity and investment of up to ₹1,250 crore.',
                'summary' => 'The proposed greenfield tractor plant would expand TAFE manufacturing capacity and support domestic and international demand for farm equipment.',
                'company_name' => 'TAFE', 'source_published_at' => '2026-08-31', 'relevance_score' => 94,
                'skills' => ['Tractor Engineering', 'Powertrain', 'Hydraulics', 'Manufacturing', 'CAD', 'CNC', 'Quality Engineering'],
                'roles' => ['Tractor Design Engineer', 'Manufacturing Engineer', 'Production Engineer', 'Testing Engineer'],
                'locations' => ['North India'], 'industries' => ['Tractors', 'Agriculture', 'Mechanical Manufacturing'],
                'career_impact' => 'Growth can create work across mechanical design, production, testing, quality, maintenance and supply-chain engineering.',
            ],
            [
                'category' => 'mechanical-engineering', 'source' => 'TAFE',
                'title' => 'TAFE and DEUTZ expand engine manufacturing in India with new Alwar production facility',
                'slug' => 'tafe-deutz-engine-manufacturing-alwar',
                'excerpt' => 'TAFE Motors and DEUTZ have started engine production at a new Alwar facility, strengthening India’s advanced engine-manufacturing ecosystem.',
                'summary' => 'The collaboration supports engine production for agricultural machinery, construction equipment, material handling and other industrial applications.',
                'company_name' => 'TAFE Motors / DEUTZ', 'source_published_at' => '2026-07-20', 'relevance_score' => 91,
                'skills' => ['IC Engines', 'Thermodynamics', 'Powertrain', 'Engine Testing', 'Machining', 'Manufacturing', 'CAD'],
                'roles' => ['Engine Design Engineer', 'Manufacturing Engineer', 'Testing Engineer', 'Powertrain Engineer'],
                'locations' => ['Alwar', 'Rajasthan'], 'industries' => ['Engine Manufacturing', 'Industrial Machinery'],
                'career_impact' => 'Engine production supports roles in design, testing, machining, assembly, quality and industrial automation.',
            ],
            [
                'category' => 'forging', 'source' => 'Mechanical Industry Desk',
                'title' => 'Vardhman and Aichi Steel build ₹1,116 crore forging and machining facility in Ludhiana',
                'slug' => 'vardhman-aichi-steel-forging-machining-ludhiana',
                'excerpt' => 'Vardhman Special Steels and Aichi Steel are establishing a forging and machining facility focused on high-value automotive components.',
                'summary' => 'The Ludhiana project focuses on precision automotive components and the adoption of advanced forging and machining technology.',
                'company_name' => 'Vardhman Special Steels / Aichi Steel', 'source_published_at' => '2026-07-27', 'relevance_score' => 96,
                'skills' => ['Forging', 'Machining', 'Metallurgy', 'CNC', 'Tool Design', 'GD&T', 'Heat Treatment'],
                'roles' => ['Forging Engineer', 'CNC Engineer', 'Tool Design Engineer', 'Metallurgy Engineer'],
                'locations' => ['Ludhiana', 'Punjab'], 'industries' => ['Forging', 'Steel', 'Automotive Components'],
                'career_impact' => 'The facility is relevant to forging, metallurgy, tooling, CNC, quality and production professionals.',
            ],
            [
                'category' => 'industrial-automation', 'source' => 'Mechanical Industry Desk',
                'title' => 'Why India’s mechanical engineering careers are shifting toward automation, EVs and advanced manufacturing',
                'slug' => 'mechanical-engineering-careers-automation-ev-advanced-manufacturing',
                'excerpt' => 'Investment in automobiles, tractors, engines, forging and EV manufacturing is changing the skill requirements for mechanical engineers.',
                'summary' => 'Modern mechanical careers increasingly combine core design and manufacturing knowledge with automation, simulation, electrification and industrial data skills.',
                'company_name' => 'Industry Intelligence', 'source_published_at' => '2026-09-07', 'relevance_score' => 97,
                'skills' => ['CAD', 'CAE', 'CATIA', 'SolidWorks', 'ANSYS', 'CNC', 'PLC', 'Robotics', 'EV', 'Battery', 'Python', 'Data Analytics'],
                'roles' => ['Mechanical Engineer', 'Automation Engineer', 'EV Engineer', 'CAD/CAE Engineer', 'Product Development Engineer'],
                'locations' => ['India'], 'industries' => ['Mechanical Engineering', 'Advanced Manufacturing', 'Electric Vehicles'],
                'career_impact' => 'Engineers who combine mechanical fundamentals with digital tools can compete for a broader set of modern manufacturing roles.',
            ],
        ];

        foreach ($articles as $index => $article) {
            $category = NewsCategory::where('slug', $article['category'])->firstOrFail();
            $source = $sources[$article['source']];
            NewsArticle::updateOrCreate(
                ['slug' => $article['slug']],
                collect($article)->except(['category', 'source'])->all() + [
                    'category_id' => $category->id,
                    'source_id' => $source->id,
                    'source_url' => config('app.url').'/latest/source/'.$article['slug'],
                    'industry_impact' => $article['summary'],
                    'student_impact' => 'Build strong fundamentals and practise the listed tools through projects, internships and production-focused case studies.',
                    'skills_impact' => 'Priority skills: '.implode(', ', $article['skills']).'.',
                    'jobs_impact' => 'Relevant roles: '.implode(', ', $article['roles']).'.',
                    'recommended_skills' => $article['skills'],
                    'entities' => [$article['company_name']],
                    'career_impact_level' => 'high',
                    'processing_status' => 'published',
                    'is_featured' => $index !== 2,
                    'is_published' => true,
                    'published_at' => now()->subMinutes($index),
                ]
            );
        }
    }
}
