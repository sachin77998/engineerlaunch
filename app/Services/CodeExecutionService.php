<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class CodeExecutionService
{
    public function execute(string $language, string $source, ?string $stdin = null): array
    {
        $languageId = config("services.judge0.languages.{$language}");
        if (!$languageId) {
            throw new RuntimeException('The selected programming language is not supported.');
        }

        $baseUrl = rtrim(config('services.judge0.url'), '/');
        $request = Http::acceptJson()->asJson()->timeout(15);
        if ($token = config('services.judge0.token')) {
            $request = $request->withHeader('X-Auth-Token', $token);
        }

        $response = $request->post($baseUrl.'/submissions?base64_encoded=false&wait=false', [
            'language_id' => $languageId,
            'source_code' => $source,
            'stdin' => $stdin,
            'cpu_time_limit' => 3,
            'wall_time_limit' => 8,
            'memory_limit' => 128000,
        ]);
        $response->throw();
        $token = $response->json('token');
        if (!$token) {
            throw new RuntimeException('The execution service did not accept the submission.');
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            usleep(250000);
            $result = $request->get($baseUrl.'/submissions/'.$token, [
                'base64_encoded' => 'false',
                'fields' => 'stdout,stderr,compile_output,message,status,time,memory',
            ]);
            $result->throw();
            $data = $result->json();
            $statusId = (int) data_get($data, 'status.id');
            if (!in_array($statusId, [1, 2], true)) {
                return [
                    'status' => data_get($data, 'status.description', 'Finished'),
                    'stdout' => $data['stdout'] ?? '',
                    'stderr' => $data['stderr'] ?? '',
                    'compile_output' => $data['compile_output'] ?? '',
                    'message' => $data['message'] ?? '',
                    'time' => $data['time'] ?? null,
                    'memory' => $data['memory'] ?? null,
                ];
            }
        }

        throw new RuntimeException('Execution is taking longer than expected. Please try again.');
    }
}
