<?php

namespace Tests\Feature;

use App\Models\Job;
use Tests\TestCase;

class JobSearchTypoTest extends TestCase
{
    public function test_rect_keyword_is_treated_as_react(): void
    {
        $reactIds = Job::active()->search('React developer')->where('country', 'India')->pluck('id');
        $typoIds = Job::active()->search('rect developer')->where('country', 'India')->pluck('id');

        $this->assertNotEmpty($reactIds);
        $this->assertSame($reactIds->sort()->values()->all(), $typoIds->sort()->values()->all());
    }
}
