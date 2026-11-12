<?php

namespace App\Services\Events\Transports;

use App\Contracts\EventTransport;
use Illuminate\Support\Facades\Redis;

// Redis Streams: one stream per topic (XADD prefix+topic MAXLEN ~ N). Consumers read with XREADGROUP.
class RedisStreamTransport implements EventTransport
{
    public function __construct(private array $config) {}

    public function send(array $events): void
    {
        $redis = Redis::connection($this->config['connection'] ?? 'default');
        foreach ($events as $e) {
            $redis->executeRaw(['XADD', ($this->config['prefix'] ?? 'events:') . $e['topic'], 'MAXLEN', '~', (string) ($this->config['maxlen'] ?? 100000), '*',
                'id', (string) $e['id'], 'key', (string) $e['key'], 'occurred_at', $e['occurred_at'], 'payload', json_encode($e['payload'], JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function name(): string { return 'redis'; }
}
