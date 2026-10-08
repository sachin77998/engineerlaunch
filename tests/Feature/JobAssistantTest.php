<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Job;
use App\Services\SkillRecommender;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JobAssistantTest extends TestCase
{
    use DatabaseTransactions;

    private function job(string $title, string $location): Job
    {
        $company = Company::firstOrCreate(['slug' => 'assistant-test-co'], ['name' => 'Assistant Test Co', 'is_active' => true]);
        return Job::create(['company_id' => $company->id, 'title' => $title, 'location' => $location, 'country' => 'India',
            'slug' => \Illuminate\Support\Str::slug($title) . '-' . uniqid(), 'status' => 'published', 'job_visibility' => 'public', 'is_active' => true, 'posted_at' => now()]);
    }

    public function test_detects_skills_across_fields_and_keeps_c_separate_from_cpp(): void
    {
        $skills = fn (string $text) => array_column(app(SkillRecommender::class)->detect($text), 'skill');
        $this->assertSame(['C'], $skills('I know C programming'));
        $this->assertEqualsCanonicalizing(['C++'], $skills('strong in c++'));
        $this->assertContains('STAAD.Pro', $skills('civil engineer with staad pro'));
        $this->assertContains('VMC Operator', $skills('3 years as vmc operator'));
        $this->assertContains('AutoCAD', $skills('mechanical, AutoCAD'));
    }

    public function test_recommends_matching_jobs_for_skills_and_city(): void
    {
        $this->job('Senior Java Developer ZQX', 'Pune, Maharashtra');
        $this->job('Java Backend Engineer ZQX', 'Noida, Uttar Pradesh');
        $this->job('Assistant Accountant ZQX', 'Pune, Maharashtra');

        $response = $this->postJson('/assistant/jobs', ['text' => 'I am good at Java', 'location' => 'Pune'])->assertOk();
        $titles = collect($response->json('jobs'))->pluck('title');
        $this->assertContains('Senior Java Developer ZQX', $titles);
        $this->assertNotContains('Java Backend Engineer ZQX', $titles);
        $this->assertNotContains('Assistant Accountant ZQX', $titles);
        $this->assertSame('Java', $response->json('skills.0.skill'));
        $this->assertStringContainsString('q=java', $response->json('search_url'));
    }

    public function test_homepage_shows_the_assistant(): void
    {
        $this->get('/')->assertOk()->assertSee('Find jobs for my skills');
    }
}
