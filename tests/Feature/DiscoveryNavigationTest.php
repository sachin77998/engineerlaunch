<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DiscoveryNavigationTest extends TestCase
{
    public function test_homepage_stats_and_requested_skills_have_destinations(): void
    {
        $html = view('portal-v2', ['dsaTracks'=>[], 'homeStats'=>['total_jobs'=>10,'total_companies'=>5,'hiring_companies'=>3,'total_technologies'=>20]])->render();
        foreach (['#jobs','/companies','#companies','#skill-directory'] as $href) {
            $this->assertStringContainsString('class="stat" href="'.$href.'"', $html);
        }
        $this->assertStringContainsString('?q=C%2B%2B#jobs', $html);
        $this->assertStringContainsString('?q=React%20Native#jobs', $html);
        $this->assertStringContainsString('/companies/dlf-cyber-city', $html);
    }

    public function test_cyber_city_directory_renders_without_seeded_company_rows(): void
    {
        $route = Route::getRoutes()->match(\Illuminate\Http\Request::create('/companies/dlf-cyber-city'));
        $this->assertSame('companies.cyber-city', $route->getName());
        $html = view('companies.cyber-city', ['companies'=>collect()])->render();
        foreach (config('cyber_city.companies') as $entry) {
            $this->assertStringContainsString($entry['name'], $html);
            $this->assertStringContainsString($entry['careers_url'], $html);
            $this->assertStringContainsString($entry['source_url'], $html);
        }
    }
}
