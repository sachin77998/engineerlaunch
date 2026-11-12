<?php

namespace App\Listeners\Property;

use App\Contracts\EventPublisher;
use App\Events\Property\BusEvent;

// Copies a domain event into the outbox; `events:relay` ships it to Kafka / Redis Streams / the log.
class PublishToEventBus
{
    public function __construct(private EventPublisher $publisher) {}

    public function handle(BusEvent $event): void
    {
        $this->publisher->publish($event->topic(), $event->payload(), $event->key());
    }
}
