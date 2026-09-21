<?php

namespace App\AI\DTO;

class AgentResponse
{
    public string $message;

    public ?string $agent;

    public array $data;

    public array $sources;

    public array $metadata;

    public bool $success;

    public function __construct(
        string $message,
        ?string $agent = null,
        array $data = [],
        array $sources = [],
        array $metadata = [],
        bool $success = true
    ) {
        $this->message = $message;
        $this->agent = $agent;
        $this->data = $data;
        $this->sources = $sources;
        $this->metadata = $metadata;
        $this->success = $success;
    }

    public static function success(
        string $message,
        ?string $agent = null,
        array $data = [],
        array $sources = [],
        array $metadata = []
    ): self {
        return new self(
            $message,
            $agent,
            $data,
            $sources,
            $metadata,
            true
        );
    }

    public static function failure(
        string $message,
        ?string $agent = null,
        array $data = [],
        array $sources = [],
        array $metadata = []
    ): self {
        return new self(
            $message,
            $agent,
            $data,
            $sources,
            $metadata,
            false
        );
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'agent' => $this->agent,
            'data' => $this->data,
            'sources' => $this->sources,
            'metadata' => $this->metadata,
        ];
    }
}
