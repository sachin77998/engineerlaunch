<?php

namespace Tests\Unit;

use App\Services\CodeExecutionService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CodeExecutionServiceTest extends TestCase
{
    public function test_it_submits_code_and_returns_the_completed_result(): void
    {
        config([
            'services.judge0.url' => 'https://judge.example',
            'services.judge0.languages.python' => 71,
        ]);

        Http::fakeSequence()
            ->push(['token' => 'submission-token'], 201)
            ->push([
                'stdout' => "Hello\n",
                'stderr' => null,
                'compile_output' => null,
                'message' => null,
                'time' => '0.01',
                'memory' => 2048,
                'status' => ['id' => 3, 'description' => 'Accepted'],
            ]);

        $result = app(CodeExecutionService::class)->execute('python', 'print("Hello")');

        $this->assertSame('Accepted', $result['status']);
        $this->assertSame("Hello\n", $result['stdout']);
    }
}
