<?php

namespace App\AI\Services;

use App\AI\DTO\AgentRequest;
use App\AI\DTO\AgentResponse;
use Illuminate\Support\Facades\Http;
use Throwable;

class AiGateway
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $timeout;
    public function __construct()
    {
        $this->apiKey = (string) env('OPENAI_API_KEY', '');
        $this->model = (string) env('OPENAI_MODEL','gpt-5.6-luna');
        $this->baseUrl = rtrim((string) env('OPENAI_BASE_URL','https://api.openai.com/v1'),'/');
        $this->timeout = (int) env('OPENAI_TIMEOUT',60);
    }
    public function generate(AgentRequest $request,?string $instructions = null): AgentResponse {
        if ($this->apiKey === '') {return AgentResponse::failure('OpenAI API key is not configured.',$request->agent);
        }
        try {
            $payload = ['model' => $this->model,'input' => $this->buildInput($request),];
            if ($instructions !== null && trim($instructions) !== '') {
                $payload['instructions'] = trim($instructions);
            }
            $response = Http::withToken($this->apiKey)->acceptJson()->asJson()->timeout($this->timeout)->post($this->baseUrl . '/responses',$payload);
            if ($response->failed()) {
                return AgentResponse::failure($this->extractApiError($response->json()),$request->agent,[],[],['http_status' => $response->status(),]);
            }
            $body = $response->json();
            $message = $this->extractText($body);
            if ($message === '') {
                return AgentResponse::failure('The AI service returned an empty response.',$request->agent,[],[],['response_id' => $body['id'] ?? null,]);
            }
            return AgentResponse::success($message,$request->agent,[],[],['response_id' => $body['id'] ?? null,'model' => $body['model'] ?? $this->model,'usage' => $body['usage'] ?? [],]);
        } catch (Throwable $e) {
            report($e);
            return AgentResponse::failure('The AI service is temporarily unavailable. Please try again.',$request->agent,[],[],['exception' => get_class($e),]);
        }
    }
    public function chat(string $message,?int $userId = null,?string $agent = null,array $context = [],array $metadata = []): AgentResponse {
        $request = new AgentRequest($message,$userId,$agent,$context,$metadata);
        return $this->generate($request);
    }
    protected function buildInput(AgentRequest $request): array
    {
        $input = [['role' => 'user','content' => [['type' => 'input_text','text' => $request->message,],],],];
        if (!empty($request->context)) {
            $contextText = $this->formatContext($request->context);
            $input[] = [
                'role' => 'user',
                'content' => [['type' => 'input_text','text' => "Additional context:\n" . $contextText,],],
            ];
        }
        return $input;
    }
    protected function formatContext(array $context): string
    {
        $json = json_encode($context,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json !== false? $json: '';
    }
    protected function extractText(array $body): string
    {
        if (isset($body['output_text']) && is_string($body['output_text'])) {return trim($body['output_text']);}
        $texts = [];
        foreach (($body['output'] ?? []) as $outputItem) {
            if (!is_array($outputItem)) {continue;}
            foreach (($outputItem['content'] ?? []) as $contentItem) {
                if (!is_array($contentItem)) {continue;}
                if (isset($contentItem['text']) && is_string($contentItem['text'])) {
                    $texts[] = $contentItem['text'];
                }
            }
        }
        return trim(implode("\n", $texts));
    }
    protected function extractApiError(array $body): string
    {
        if (isset($body['error']['message']) && is_string($body['error']['message'])) {return $body['error']['message'];}
        if (isset($body['message']) && is_string($body['message'])) {return $body['message'];}
        return 'Unable to communicate with the AI service.';
    }
}
