<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('jobs:dispatch-daily --triggered-by=scheduler')
            ->dailyAt('02:00')
            ->withoutOverlapping(180);
        $schedule->command('queue:work database --queue=catalog,feeds,discovery,ingestion --stop-when-empty --max-time=300 --tries=3 --timeout=900')
            ->everyMinute()
            ->withoutOverlapping(180);
        $schedule->command('queue:work --queue=resume-processing,emails,default --stop-when-empty --max-time=300 --tries=3 --timeout=300')
            ->everyMinute()
            ->withoutOverlapping(15);
        $schedule->command('jobs:import-feeds')->everySixHours()->withoutOverlapping(120);
        $schedule->command('companies:discover all --country=IN --country=US --country=GB --country=DE --industrial-areas --sync')->weeklyOn(0, '01:00')->withoutOverlapping(720);
        $schedule->command('companies:discover gleif --gleif-cities --gleif-country=AE --gleif-pages=10')->monthlyOn(2, '02:30')->withoutOverlapping(1440);
        $schedule->command('premium:build-recommendations')
            ->dailyAt('08:00')
            ->withoutOverlapping(30);
        $schedule->command('candidates:auto-apply')
            ->dailyAt('08:15')
            ->withoutOverlapping(30);
        $schedule->command('news:fetch')->hourly()->withoutOverlapping(30);
        $schedule->command('news:fetch-industry --industry=all --limit=10')->hourly()->withoutOverlapping(55);
        $schedule->command('news:process')->everyThirtyMinutes()->withoutOverlapping(30);
        $schedule->command('news:publish')->everyThirtyMinutes()->withoutOverlapping(30);
        $schedule->command('news:notify')->dailyAt('08:00')->withoutOverlapping(30);
        $schedule->command('portal:send-daily-events --limit=100')->dailyAt('09:00')->withoutOverlapping(120);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
