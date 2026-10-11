<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class JobRecommendationTest extends TestCase
{
    use DatabaseTransactions;

    private function job(string $title, array $extra = []): Job
    {
        $company = Company::firstOrCreate(['slug' => 'recommendation-test-co'], ['name' => 'Recommendation Test Co', 'is_active' => true]);
        return Job::create($extra + ['company_id' => $company->id, 'title' => $title, 'location' => 'Pune, Maharashtra', 'country' => 'India',
            'slug' => Str::slug($title) . '-' . uniqid(), 'status' => 'published', 'job_visibility' => 'public', 'is_active' => true, 'posted_at' => now()]);
    }

    public function test_search_history_recommends_matching_and_related_jobs_with_reasons(): void
    {
        $this->job('C++ Developer QZRX');
        $this->job('Embedded C Firmware Engineer QZRX');
        $this->job('Accounts Executive QZRX');
        $signals = json_encode([['kind' => 'search', 'value' => 'c++', 'at' => time()]]);

        $response = $this->getJson('/recommendations/jobs?limit=20&signals=' . urlencode($signals))->assertOk();
        $titles = collect($response->json('jobs'))->pluck('title');
        $this->assertContains('C++ Developer QZRX', $titles);
        $this->assertNotContains('Accounts Executive QZRX', $titles);
        $this->assertSame(['C++'], $response->json('because'));
        $top = collect($response->json('jobs'))->firstWhere('title', 'C++ Developer QZRX');
        $this->assertContains('Matches C++', $top['reasons']);
        $this->assertGreaterThan(50, $top['match']);
    }

    public function test_assistant_uses_passout_batch_for_experience(): void
    {
        $this->job('Senior PHP Laravel Developer QZRX', ['experience_min' => 3, 'experience_max' => 6]);
        $response = $this->postJson('/assistant/jobs', ['text' => 'PHP, Laravel', 'education' => 'B.Tech CSE', 'passout_year' => now()->year - 4])->assertOk();
        $this->assertSame(4, $response->json('experience'));
        $this->assertStringContainsString('batch', $response->json('career_note'));
        $job = collect($response->json('recommended'))->firstWhere('title', 'Senior PHP Laravel Developer QZRX');
        $this->assertNotNull($job);
        $this->assertContains('Fits your 4 yrs experience', $job['reasons']);
    }

    public function test_candidate_tracks_applications_with_applicant_pool(): void
    {
        $job = $this->job('Java Developer QZRX');
        $user = User::factory()->create(['role' => 'student', 'role_code' => 1]);
        $other = User::factory()->create(['role' => 'student', 'role_code' => 1]);
        JobApplication::create(['job_id' => $job->id, 'user_id' => $user->id, 'status' => 'in_progress', 'applied_at' => now()]);
        JobApplication::create(['job_id' => $job->id, 'user_id' => $other->id, 'status' => 'rejected', 'applied_at' => now()]);

        $this->actingAs($user)->get('/my-applications')->assertOk()
            ->assertSee('Java Developer QZRX')->assertSee('2</b> applied', false)->assertSee('1</b> rejected', false);
    }
}
