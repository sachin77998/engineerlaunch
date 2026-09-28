<?php

namespace Tests\Unit;

use Database\Seeders\OfficialCareerSourceSeeder;
use PHPUnit\Framework\TestCase;

class OfficialCareerSourceSeederTest extends TestCase
{
    public function test_every_enabled_source_has_a_supported_official_configuration(): void
    {
        $sources = OfficialCareerSourceSeeder::sources();
        $this->assertCount(count($sources), array_unique(array_column($sources, 'name')));
        foreach ($sources as $source) {
            $this->assertStringStartsWith('https://', $source['website']);
            $this->assertStringStartsWith('https://', $source['careers_url']);
            if (!($source['sync_enabled'] ?? isset($source['ats_provider']))) continue;
            $this->assertContains($source['ats_provider'], ['greenhouse','lever','workday','smartrecruiters','amazon','icims_jibe','successfactors']);
            if (in_array($source['ats_provider'], ['greenhouse','lever','smartrecruiters'])) {
                $this->assertNotEmpty($source['ats_identifier']);
            }
            if ($source['ats_provider'] === 'workday') $this->assertStringStartsWith('https://', $source['jobs_feed_url']);
        }
        $boards = array_column($sources, 'ats_identifier');
        foreach (['mongodb','grafanalabs','canonical','datadog','cloudflare','discord'] as $board) $this->assertContains($board, $boards);
    }
}
