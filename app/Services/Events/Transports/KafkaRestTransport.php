<?php

namespace App\Services\Events\Transports;

use App\Contracts\EventTransport;
use Illuminate\Support\Facades\Http;
use RuntimeException;

// Apache Kafka through the Confluent REST Proxy (v2 API): POST /topics/<topic> with JSON records. The event key keeps
// one entity's events on one partition, so they stay in order.
class KafkaRestTransport implements EventTransport
{
    public function __construct(private array $config) {}

    public function send(array $events): void
    {
        $base = rtrim((string) ($this->config['rest_proxy'] ?? ''), '/');
        if ($base === '') throw new RuntimeException('KAFKA_REST_PROXY_URL is not set.');
        foreach (collect($events)->groupBy('topic') as $topic => $batch) {
            $req = Http::timeout($this->config['timeout'] ?? 10)->withHeaders(['Content-Type' => 'application/vnd.kafka.json.v2+json', 'Accept' => 'application/vnd.kafka.v2+json']);
            if (!empty($this->config['username'])) $req = $req->withBasicAuth($this->config['username'], (string) $this->config['password']);
            $res = $req->withBody(json_encode(['records' => $batch->map(fn ($e) => ['key' => $e['key'], 'value' => ['id' => $e['id'], 'occurred_at' => $e['occurred_at'], 'payload' => $e['payload']]])->values()->all()]), 'application/vnd.kafka.json.v2+json')
                ->post($base . '/topics/' . rawurlencode(($this->config['topic_prefix'] ?? '') . $topic));
            if (!$res->successful()) throw new RuntimeException("Kafka REST proxy rejected topic $topic: HTTP " . $res->status() . ' ' . substr($res->body(), 0, 200));
            foreach ((array) $res->json('offsets') as $o) {
                if (!empty($o['error'])) throw new RuntimeException("Kafka rejected a record on $topic: " . $o['error']);
            }
        }
    }

    public function name(): string { return 'kafka'; }
}
