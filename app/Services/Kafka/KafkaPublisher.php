<?php

namespace App\Services\Kafka;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class KafkaPublisher
{
    public function enabled(): bool
    {
        return config('kafka.enabled') && class_exists(\RdKafka\Producer::class);
    }

    public function publish(string $topic, string $type, array $data, ?string $key = null): bool
    {
        return $this->publishMany($topic, [['type' => $type, 'data' => $data, 'key' => $key]]);
    }

    public function publishMany(string $topic, array $messages): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        try {
            $producer = new \RdKafka\Producer($this->configuration());
            $kafkaTopic = $producer->newTopic($topic);
            foreach ($messages as $message) {
                $payload = json_encode([
                    'id' => (string) Str::uuid(),
                    'type' => $message['type'],
                    'occurred_at' => now()->toIso8601String(),
                    'data' => $message['data'],
                ], JSON_THROW_ON_ERROR);
                $kafkaTopic->produce(RD_KAFKA_PARTITION_UA, 0, $payload, $message['key'] ?? null);
                $producer->poll(0);
            }

            return $producer->flush(config('kafka.flush_timeout_ms')) === RD_KAFKA_RESP_ERR_NO_ERROR;
        } catch (\Throwable $exception) {
            report($exception);
            Log::warning('Kafka publish failed; caller will use its durable fallback.', ['topic' => $topic, 'error' => $exception->getMessage()]);
            return false;
        }
    }

    public function configuration(): \RdKafka\Conf
    {
        $config = new \RdKafka\Conf();
        $config->set('bootstrap.servers', config('kafka.brokers'));
        $config->set('client.id', config('kafka.client_id'));
        $config->set('enable.idempotence', 'true');
        $config->set('acks', 'all');
        $config->set('compression.type', 'snappy');
        $config->set('message.timeout.ms', '5000');
        $securityProtocol = config('kafka.security_protocol');
        if ($securityProtocol !== 'plaintext') {
            $config->set('security.protocol', $securityProtocol);
            if (config('kafka.sasl_mechanism')) $config->set('sasl.mechanism', config('kafka.sasl_mechanism'));
            if (config('kafka.sasl_username')) $config->set('sasl.username', config('kafka.sasl_username'));
            if (config('kafka.sasl_password')) $config->set('sasl.password', config('kafka.sasl_password'));
        }

        return $config;
    }
}
