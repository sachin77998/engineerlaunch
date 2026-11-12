<?php

namespace App\Contracts;

// Delivers stored events to a message broker (Kafka, Redis Streams, log…). Throws on failure so the relay retries.
interface EventTransport
{
    /** @param array<int, array{id:int, topic:string, key:?string, payload:array, occurred_at:string}> $events */
    public function send(array $events): void;

    public function name(): string;
}
