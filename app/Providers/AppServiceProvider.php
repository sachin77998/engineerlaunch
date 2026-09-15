<?php

namespace App\Providers;

use App\Models\Resume;
use App\Observers\ResumeObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->scoped(\App\Services\IndustrialCareerMatcher::class, fn () => new \App\Services\IndustrialCareerMatcher());
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Resume::observe(ResumeObserver::class);
    }
}
