<?php

namespace Tests\Unit;

use App\Services\Kafka\KafkaPublisher;
use Tests\TestCase;

class KafkaPublisherTest extends TestCase
{
    public function test_it_safely_declines_publish_when_kafka_is_disabled(): void
    {
        config(['kafka.enabled' => false]);
        $publisher = app(KafkaPublisher::class);
        $this->assertFalse($publisher->enabled());
        $this->assertFalse($publisher->publish('test-topic', 'test.event', ['ok' => true]));
    }
}
