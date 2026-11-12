<?php

namespace App\Contracts;

// Publishes a domain event (topic + payload) for other parts of the system to consume.
interface EventPublisher
{
    public function publish(string $topic, array $payload, ?string $key = null): void;
}
