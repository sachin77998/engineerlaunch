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
        // @includeSafe('view', [...]): an optional page section that is logged and skipped if it fails, instead of a 500 for the whole page.
        \Illuminate\Support\Facades\Blade::directive('includeSafe', fn ($expression) => "<?php try { echo \$__env->make({$expression}, \\Illuminate\\Support\\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); } catch (\\Throwable \$__e) { report(\$__e); } ?>");
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function ($user, string $token) {
            return rtrim((string) config('app.url'), '/').'/reset-password/'.rawurlencode($token).'?email='.rawurlencode($user->getEmailForPasswordReset());
        });

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Resume::observe(ResumeObserver::class);
    }
}
