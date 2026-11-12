<?php

namespace App\Providers;

use App\Contracts\EventPublisher;
use App\Contracts\EventTransport;
use App\Services\Events\EventTransportFactory;
use App\Services\Events\OutboxEventPublisher;
use App\Services\Property\PropertyOptions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Throwable;

// Wires the property module: database-backed option lists into config, the event publisher driver, model observers and extension registries.
class PropertyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PropertyOptions::class);
        $this->app->singleton(EventPublisher::class, OutboxEventPublisher::class);
        $this->app->bind(EventTransport::class, fn () => EventTransportFactory::make(config('events_bus.transport')));
    }

    public function boot(): void
    {
        // Database option lists override config('properties.*'). If the tables or Redis are unavailable (fresh install, migration), config values stay.
        try {
            $this->app->make(PropertyOptions::class)->apply();
        } catch (Throwable $e) {
            if (!$this->app->runningInConsole()) report($e);
        }
        // Short type names stored in polymorphic columns (site_visits.visitable_type).
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap(['property' => \App\Models\Property\Property::class, 'project' => \App\Models\Property\Project::class]);
        \App\Models\Property\Property::observe(\App\Observers\PropertyObserver::class);
        \App\Models\Property\Project::observe(\App\Observers\ProjectObserver::class);
        foreach (config('events_bus.listeners', []) as $event => $listeners) {
            foreach ($listeners as $listener) Event::listen($event, $listener);
        }
    }
}
