<?php

namespace App\Providers;

use App\Models\Resume;
use App\Observers\ResumeObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Resume::observe(ResumeObserver::class);
    }
}
