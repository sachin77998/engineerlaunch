<?php

namespace Tests\Unit;

use Database\Seeders\OfficialCareerSourceSeeder;
use PHPUnit\Framework\TestCase;

class OfficialCareerSourceSeederTest extends TestCase
{
    public function test_every_source_has_a_supported_complete_official_configuration(): void
    {
        $sources = OfficialCareerSourceSeeder::sources();

        $this->assertNotEmpty($sources);
        $this->assertCount(count($sources), array_unique(array_column($sources, 'name')));
        $this->assertCount(count($sources), array_unique(array_column($sources, 'ats_identifier')));

        foreach ($sources as $source) {
            $this->assertSame('greenhouse', $source['ats_provider']);
            $this->assertNotSame('', trim($source['ats_identifier']));
            $this->assertStringStartsWith('https://', $source['website']);
            $this->assertStringStartsWith('https://', $source['careers_url']);
        }
    }
}
