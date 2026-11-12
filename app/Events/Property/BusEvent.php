<?php

namespace App\Events\Property;

// A Laravel event that is also published on the domain event bus (outbox → Kafka / Redis Streams).
interface BusEvent
{
    public function topic(): string;

    public function key(): ?string;

    public function payload(): array;
}
