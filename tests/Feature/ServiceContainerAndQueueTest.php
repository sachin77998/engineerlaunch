<?php

namespace Tests\Feature;

use App\Contracts\NewsFetcher;
use App\Jobs\MatchResumeToHrJobs;
use App\Jobs\ParseCandidateResume;
use App\Jobs\ProcessIngestedJob;
use App\Jobs\SendPortalEventEmailJob;
use App\Models\NewsSource;
use App\Models\Resume;
use App\Observers\ResumeObserver;
use App\Services\GeographyCatalog;
use App\Services\IndustrialCareerMatcher;
use App\Services\News\NewsFetcherService;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ServiceContainerAndQueueTest extends TestCase
{
    public function test_lookup_services_are_shared_and_reset_between_lifecycles(): void
    {
        foreach ([GeographyCatalog::class, IndustrialCareerMatcher::class] as $class) {
            $first = $this->app->make($class);
            $this->assertSame($first, $this->app->make($class));
            $this->app->forgetScopedInstances();
            $this->assertNotSame($first, $this->app->make($class));
        }
    }

    public function test_news_contract_resolves_and_can_be_replaced(): void
    {
        $this->assertInstanceOf(NewsFetcherService::class, $this->app->make(NewsFetcher::class));
        $fake = new class implements NewsFetcher
        {
            public function fetch(NewsSource $source): array
            {
                return [];
            }
        };
        $this->app->instance(NewsFetcher::class, $fake);
        $this->assertSame($fake, $this->app->make(NewsFetcher::class));
    }

    public function test_jobs_have_routes_and_wait_for_commit(): void
    {
        $this->assertSame('database', (new ProcessIngestedJob([]))->connection);
        foreach ([new ParseCandidateResume(1), new MatchResumeToHrJobs(1), new ProcessIngestedJob([]), new SendPortalEventEmailJob(1)] as $job) {
            $this->assertTrue($job->afterCommit);
            $this->assertContains($job->queue, ['resume-processing', 'ingestion', 'emails']);
            $this->assertLessThan(config('queue.connections.database.retry_after'), $job->timeout);
        }
    }

    public function test_resume_observer_queues_matching_only_after_processing(): void
    {
        Queue::fake();
        $resume = new Resume;
        $resume->forceFill(['id' => 1, 'parsing_status' => 'queued']);
        $resume->syncOriginal();
        $observer = new ResumeObserver;
        $observer->updated($resume);
        Queue::assertNothingPushed();
        $resume->parsing_status = 'processed';
        $resume->syncChanges();
        $observer->updated($resume);
        Queue::assertPushedOn('resume-processing', MatchResumeToHrJobs::class);
    }

    private function isolatedDatabase(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        \Illuminate\Support\Facades\DB::purge('sqlite');
    }

    public function test_queue_dispatch_waits_for_commit_and_is_discarded_on_rollback(): void
    {
        $this->isolatedDatabase();
        (require database_path('migrations/2026_08_21_000009_create_ingestion_queue_tables.php'))->up();
        config(['queue.default' => 'database']);
        $db = \Illuminate\Support\Facades\DB::connection();
        $db->beginTransaction();
        ParseCandidateResume::dispatch(1);
        $this->assertSame(0, $db->table('jobs_queue')->count());
        $db->rollBack();
        $this->assertSame(0, $db->table('jobs_queue')->count());
        $db->beginTransaction();
        ParseCandidateResume::dispatch(2);
        $this->assertSame(0, $db->table('jobs_queue')->count());
        $db->commit();
        $this->assertSame(1, $db->table('jobs_queue')->count());
        $this->assertSame('resume-processing', $db->table('jobs_queue')->value('queue'));
    }

    public function test_candidate_batch_queues_across_chunks_and_requires_consent(): void
    {
        $this->isolatedDatabase();
        $schema = \Illuminate\Support\Facades\Schema::getFacadeRoot();
        $schema->create('candidate_auto_apply_preferences', function ($table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->boolean('enabled');
            $table->timestamp('consented_at')->nullable();
        });
        $schema->create('candidate_profiles', function ($table) {
            $table->id();
            $table->unsignedInteger('user_id');
        });
        $schema->create('resumes', function ($table) {
            $table->id();
            $table->unsignedInteger('candidate_profile_id');
            $table->boolean('is_primary');
            $table->string('parsing_status');
            $table->timestamp('parsed_at');
        });
        for ($id = 1; $id <= 103; $id++) {
            \Illuminate\Support\Facades\DB::table('candidate_auto_apply_preferences')->insert(['id' => $id, 'user_id' => $id, 'enabled' => $id !== 103, 'consented_at' => $id === 102 ? null : now()]);
            \Illuminate\Support\Facades\DB::table('candidate_profiles')->insert(['id' => $id, 'user_id' => $id]);
            \Illuminate\Support\Facades\DB::table('resumes')->insert(['id' => $id, 'candidate_profile_id' => $id, 'is_primary' => true, 'parsing_status' => 'processed', 'parsed_at' => now()]);
        }
        Queue::fake();
        $this->artisan('candidates:auto-apply')->expectsOutput('Queued 101 resumes for matching and candidate-authorized internal applications.')->assertSuccessful();
        Queue::assertPushed(MatchResumeToHrJobs::class, 101);
        Queue::assertPushedOn('resume-processing', MatchResumeToHrJobs::class, fn ($job) => $job->resumeId === 101);
        Queue::assertNotPushed(MatchResumeToHrJobs::class, fn ($job) => in_array($job->resumeId, [102, 103], true));
    }
}
