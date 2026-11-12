<?php

// Domain event bus (outbox pattern). Events are written to the domain_events table in the same request, then
// `php artisan events:relay` (scheduled every minute) forwards them to the transport:
//   log    — writes to the Laravel log (default; no extra infrastructure)
//   redis  — Redis Streams (XADD <prefix><topic>), consumable with XREADGROUP
//   kafka  — Apache Kafka via the Confluent REST Proxy (no PHP extension needed)
//   null   — keep events in the outbox only
// Switching transport is a config change; publishers and consumers don't change.
return [
    'transport' => env('EVENT_BUS_TRANSPORT', 'log'),
    'batch' => 500,
    'max_attempts' => 10,
    'keep_days' => 30, // published events are pruned after this many days
    'redis' => ['connection' => env('EVENT_BUS_REDIS_CONNECTION', 'default'), 'prefix' => env('EVENT_BUS_REDIS_PREFIX', 'events:'), 'maxlen' => 100000],
    'kafka' => [
        'rest_proxy' => env('KAFKA_REST_PROXY_URL'), // e.g. http://kafka-rest:8082
        'username' => env('KAFKA_REST_USERNAME'),
        'password' => env('KAFKA_REST_PASSWORD'),
        'topic_prefix' => env('KAFKA_TOPIC_PREFIX', 'naukri.'),
        'timeout' => 10,
    ],
    // Event → listeners. Every App\Events\Property\BusEvent is also copied to the outbox by PublishToEventBus.
    // A new consumer is a new listener class added here; publishers don't change.
    'listeners' => [
        App\Events\Property\PropertyChanged::class => [App\Listeners\Property\PublishToEventBus::class, App\Listeners\Property\FlushPropertyCache::class, App\Listeners\Property\QueueNearbyPlaces::class, App\Listeners\Property\QueuePropertyAlerts::class],
        App\Events\Property\ProjectChanged::class => [App\Listeners\Property\PublishToEventBus::class, App\Listeners\Property\FlushPropertyCache::class, App\Listeners\Property\QueueNearbyPlaces::class],
        App\Events\Property\PropertyViewed::class => [App\Listeners\Property\PublishToEventBus::class, App\Listeners\Property\RecordPropertyView::class],
        App\Events\Property\ProjectViewed::class => [App\Listeners\Property\PublishToEventBus::class, App\Listeners\Property\RecordProjectView::class],
        App\Events\Property\SiteVisitChanged::class => [App\Listeners\Property\PublishToEventBus::class, App\Listeners\Property\NotifySiteVisit::class],
        App\Events\Property\PropertySearched::class => [App\Listeners\Property\PublishToEventBus::class, App\Listeners\Property\RecordPropertySearch::class],
    ],
];
