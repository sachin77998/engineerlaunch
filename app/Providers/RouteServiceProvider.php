<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Do Trading: each kind of request has its own counter, so fee previews never use up the order or export allowance.
        foreach (['trading-read' => 180, 'trading-order' => 20, 'trading-otp' => 15, 'trading-kyc' => 40, 'trading-export' => 10] as $name => $perMinute) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($perMinute)->by($name . ':' . ($request->user()?->id ?: $request->ip())));
        }

        // Site-wide chat assistant: each message may call the AI provider, so keep a per-visitor cap.
        RateLimiter::for('assistant', fn (Request $request) => [
            Limit::perMinute(12)->by('assistant:m:' . ($request->user()?->id ?: $request->ip())),
            Limit::perDay(200)->by('assistant:d:' . ($request->user()?->id ?: $request->ip())),
        ]);

        RateLimiter::for('discovery', function (Request $request) {
            return [
                Limit::perMinute(60)->by('discovery:'.$request->ip()),
                Limit::perHour(1200)->by('discovery-hour:'.$request->ip()),
            ];
        });

        RateLimiter::for('auth-api', function (Request $request) {
            $identity = Str::lower((string) $request->input('email', $request->ip()));

            return [
                Limit::perMinute(5)->by('auth:'.$identity.'|'.$request->ip()),
                Limit::perHour(30)->by('auth-hour:'.$request->ip()),
            ];
        });

        RateLimiter::for('ingestion', function (Request $request) {
            $identity = hash('sha256', (string) $request->header('X-Ingestion-Key', $request->ip()));

            return Limit::perMinute(10)->by('ingestion:'.$identity);
        });
    }
}
