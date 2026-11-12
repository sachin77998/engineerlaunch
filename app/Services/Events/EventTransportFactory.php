<?php

namespace App\Services\Events;

use App\Contracts\EventTransport;
use App\Services\Events\Transports\KafkaRestTransport;
use App\Services\Events\Transports\LogTransport;
use App\Services\Events\Transports\NullTransport;
use App\Services\Events\Transports\RedisStreamTransport;
use InvalidArgumentException;

// Picks the broker transport from config('events_bus.transport'). Add a transport by adding a class and a line here.
class EventTransportFactory
{
    public static function make(?string $name): EventTransport
    {
        return match ($name ?: 'log') {
            'log' => new LogTransport,
            'redis' => new RedisStreamTransport(config('events_bus.redis')),
            'kafka' => new KafkaRestTransport(config('events_bus.kafka')),
            'null', 'none' => new NullTransport,
            default => throw new InvalidArgumentException("Unknown event transport [$name]."),
        };
    }
}
