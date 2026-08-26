<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('jobs:dispatch-daily')
            ->dailyAt('02:00')
            ->withoutOverlapping(180);
        $schedule->command('queue:work database --queue=ingestion --stop-when-empty --tries=3 --timeout=900')
            ->dailyAt('02:01')
            ->withoutOverlapping(180);
        $schedule->command('queue:work database --queue=resume-processing --stop-when-empty --tries=2 --timeout=300')
            ->everyMinute()
            ->withoutOverlapping(15);
        $schedule->command('premium:build-recommendations')
            ->dailyAt('08:00')
            ->withoutOverlapping(30);
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
