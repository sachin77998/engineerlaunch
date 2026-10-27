<?php

namespace App\Providers;

use App\Contracts\NewsFetcher;
use App\Services\GeographyCatalog;
use App\Services\IndustrialCareerMatcher;
use App\Services\News\NewsFetcherService;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Share lookup caches within a request/job, then reset them for the next lifecycle.
        $this->app->scoped(GeographyCatalog::class);
        // One workflow engine per request/job, so its re-entry guard sees nested triggers.
        $this->app->scoped(\App\Services\Hiring\Workflows::class);
        $this->app->scoped(IndustrialCareerMatcher::class);
        $this->app->scoped(\App\Services\Hiring\Team::class);
        $this->app->bind(NewsFetcher::class, NewsFetcherService::class);
    }
}
