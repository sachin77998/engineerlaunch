<?php

namespace Tests\Unit;

use App\Services\CandidateResumeTextParser;
use Tests\TestCase;

class CandidateResumeTextParserTest extends TestCase
{
    public function test_it_extracts_profile_fields_from_a_two_column_resume(): void
    {
        $text = <<<'RESUME'
Sachin Soni
sachin@example.com
6283570676
Hoshiarpur, India 146001

Skills
Java
Laravel

Experience
• Data Structures and Algorithms NovoInvent Software Pvt Ltd - Software Developer
Noida, India
05/2022 - Current

Education And Training
Bachelor Of Technology
Computer Science Engineering
Chitkara University Rajpura • Solved production issues
RESUME;

        $data = app(CandidateResumeTextParser::class)->parse($text, ['Java', 'Laravel', 'Data Structures', 'Algorithms']);

        $this->assertSame('Sachin', $data['first_name']);
        $this->assertSame('Soni', $data['last_name']);
        $this->assertSame('Hoshiarpur, India', $data['location']);
        $this->assertSame('NovoInvent Software Pvt Ltd', $data['current_company']);
        $this->assertSame('Software Developer', $data['designation']);
        $this->assertSame('Chitkara University Rajpura', $data['education']);
        $this->assertSame(['Java', 'Laravel', 'Data Structures', 'Algorithms'], $data['skills']);
        $this->assertGreaterThan(3, $data['experience']);
    }
}
