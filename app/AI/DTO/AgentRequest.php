<?php

namespace App\AI\DTO;

class AgentRequest
{
    public string $message;
    public ?int $userId;
    public ?string $agent;
    public array $context;
    public array $metadata;
    public function __construct(string $message,?int $userId = null,?string $agent = null,array $context = [],array $metadata = []) {
        $this->message = $message;
        $this->userId = $userId;
        $this->agent = $agent;
        $this->context = $context;
        $this->metadata = $metadata;
    }

    public static function fromArray(array $data): self
    {
        return new self(trim((string) ($data['message'] ?? '')),isset($data['user_id']) ? (int) $data['user_id'] : null,isset($data['agent']) && $data['agent'] !== ''? (string) $data['agent']: null,
            is_array($data['context'] ?? null)
                ? $data['context']
                : [],
            is_array($data['metadata'] ?? null)
                ? $data['metadata']
                : []
        );
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'user_id' => $this->userId,
            'agent' => $this->agent,
            'context' => $this->context,
            'metadata' => $this->metadata,
        ];
    }

    public function hasUser(): bool
    {
        return $this->userId !== null;
    }

    public function hasAgent(): bool
    {
        return $this->agent !== null && $this->agent !== '';
    }
}
