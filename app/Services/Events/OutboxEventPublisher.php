<?php

namespace App\Services\Events;

use App\Contracts\EventPublisher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Writes events to the domain_events outbox (inside any open transaction); EventRelay forwards them to the broker.
class OutboxEventPublisher implements EventPublisher
{
    public function publish(string $topic, array $payload, ?string $key = null): void
    {
        DB::table('domain_events')->insert([
            'uuid' => (string) Str::uuid(), 'topic' => $topic, 'key' => $key, 'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'occurred_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
