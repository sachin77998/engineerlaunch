<?php

namespace Tests\Unit;

use App\Services\InstitutionLookupService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstitutionLookupServiceTest extends TestCase
{
    public function test_it_searches_indian_institutions_and_excludes_other_countries(): void
    {
        Cache::forget('institution-directory:v1');
        Http::fake([
            '*' => Http::response([
                ['name' => 'Example Institute of Technology', 'country' => 'India'],
                ['name' => 'Example University', 'country' => 'Canada'],
            ]),
        ]);

        $results = app(InstitutionLookupService::class)->search('Example');

        $this->assertSame(['Example Institute of Technology'], $results);
    }

    public function test_it_uses_local_institutions_when_the_directory_is_unavailable(): void
    {
        Cache::forget('institution-directory:v1');
        Http::fake(fn () => Http::response([], 503));

        $results = app(InstitutionLookupService::class)->search('Delhi Technological');

        $this->assertContains('Delhi Technological University', $results);
    }
}
