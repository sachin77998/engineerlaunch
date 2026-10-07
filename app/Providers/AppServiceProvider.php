<?php

namespace App\Providers;

use App\Models\Resume;
use App\Observers\ResumeObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
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
        // Domain bindings live in DomainServiceProvider.
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapFive();
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function ($user, string $token) {
            return rtrim((string) config('app.url'), '/').'/reset-password/'.rawurlencode($token).'?email='.rawurlencode($user->getEmailForPasswordReset());
        });

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Resume::observe(ResumeObserver::class);
    }
}
