<?php

namespace Tests\Unit;

use App\Services\JobClassificationService;
use PHPUnit\Framework\TestCase;

class JobClassificationServiceTest extends TestCase
{
    /** @dataProvider jobExamples */
    public function test_it_classifies_jobs_by_department_and_discipline(array $job, string $department, string $discipline): void
    {
        $result = (new JobClassificationService)->classify($job);

        $this->assertSame($department, $result['department']);
        $this->assertSame($discipline, $result['engineering_discipline']);
    }

    public static function jobExamples(): array
    {
        return [
            'software quality' => [['title' => 'SDET - Java Test Automation'], 'Quality & Testing', 'Computer Science & Software Engineering'],
            'mechanical' => [['title' => 'Senior Mechanical CAD Engineer'], 'Engineering - Mechanical', 'Mechanical Engineering'],
            'chemical' => [['title' => 'Chemical Process Engineer'], 'Engineering - Chemical & Process', 'Chemical & Process Engineering'],
            'civil' => [['title' => 'Civil Structural Engineer'], 'Engineering - Civil & Construction', 'Civil & Construction Engineering'],
        ];
    }
}
