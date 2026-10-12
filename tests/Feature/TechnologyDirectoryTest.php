<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Job;
use App\Models\Technology;
use App\Services\Technologies\TechnologyCatalog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class TechnologyDirectoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_ambiguous_names_match_only_in_programming_context(): void
    {
        foreach (['Go' => 'Programming Languages', 'Perl' => 'Programming Languages', 'C' => 'Programming Languages'] as $name => $category) {
            Technology::firstOrCreate(['name' => $name], ['slug' => Str::slug($name) . '-' . uniqid(), 'category' => $category]);
        }
        $catalog = app(TechnologyCatalog::class);
        $matchers = $catalog->matchers();
        $names = fn (string $text) => Technology::whereIn('id', $catalog->match($matchers, $text))->pluck('name')->all();

        $this->assertContains('Go', $names('Senior Go Developer'));
        $this->assertContains('Go', $names('Java, Go, Rust'));
        $this->assertNotContains('Go', $names('Go-to-Market Lead'));
        $this->assertNotContains('C', $names('C-Level Executive'));
        $this->assertNotContains('C', $names('HRBP / C&B Manager'));
        $this->assertContains('Perl', $names('Perl Developer'));
    }

    public function test_directory_lists_technologies_and_opens_their_jobs(): void
    {
        $perl = Technology::firstOrCreate(['name' => 'Perl'], ['slug' => 'perl-' . uniqid(), 'category' => 'Programming Languages']);
        $company = Company::firstOrCreate(['slug' => 'tech-directory-test-co'], ['name' => 'Tech Directory Test Co', 'is_active' => true]);
        $job = Job::create(['company_id' => $company->id, 'title' => 'Perl Developer ZQTD', 'location' => 'Pune', 'country' => 'India',
            'slug' => 'perl-developer-zqtd-' . uniqid(), 'status' => 'published', 'job_visibility' => 'public', 'is_active' => true, 'posted_at' => now()]);
        $job->technologies()->syncWithoutDetaching([$perl->id]);
        \Illuminate\Support\Facades\Cache::forget('technologies:directory');

        $this->get('/technologies')->assertOk()->assertSee('Perl')->assertSee(route('technologies.show', $perl->slug), false);
        $this->get('/technologies/' . $perl->slug)->assertOk()->assertSee('Perl Developer ZQTD');
        $this->get('/technologies/not-a-technology-zz')->assertNotFound();
    }
}
