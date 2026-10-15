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
        $schedule->command('queue:work database --queue=maintenance,catalog,feeds,discovery,ingestion --stop-when-empty --max-time=300 --tries=3 --timeout=900')
            ->everyMinute()
            ->withoutOverlapping(180);
        $schedule->command('queue:work --queue=resume-processing,emails,default --stop-when-empty --max-time=300 --tries=3 --timeout=300')
            ->everyMinute()
            ->withoutOverlapping(15);
        // MCA company register import (data.gov.in): its own worker so discovery work never holds it up.
        $schedule->command('queue:work database --queue=registry --stop-when-empty --max-time=280 --tries=3 --timeout=600')
            ->everyMinute()
            ->withoutOverlapping(15);
        // Do Trading: holidays, NAVs and margins after AMFI publishes (late evening), allotment, returns weekly, end-of-day export.
        $schedule->command('trading:sync --holidays --navs --margins --allot')->dailyAt('22:30')->timezone('Asia/Kolkata')->withoutOverlapping(60);
        $schedule->command('trading:sync --navs --allot')->dailyAt('08:30')->timezone('Asia/Kolkata')->withoutOverlapping(60);
        $schedule->command('trading:sync --returns')->weeklyOn(0, '06:00')->timezone('Asia/Kolkata')->withoutOverlapping(60);
        $schedule->command('trading:eod-export')->weekdays()->at('23:45')->timezone('Asia/Kolkata')->withoutOverlapping(30);
        // NSE-listed companies: audited results, shareholding and prices weekly (only companies not synced in a week); prices daily.
        $schedule->command('insights:import-nse')->weeklyOn(6, '21:00')->timezone('Asia/Kolkata')->withoutOverlapping(120);
        $schedule->command('insights:import-nse --prices')->weekdays()->at('19:30')->timezone('Asia/Kolkata')->withoutOverlapping(30);
        // Re-reads the register monthly for new incorporations and status changes.
        $schedule->command('insights:import-mca')->monthlyOn(5, '00:30')->withoutOverlapping(60);
        $schedule->command('jobs:import-feeds')->everySixHours()->withoutOverlapping(120);
        $schedule->command('companies:discover all --country=IN --country=US --country=GB --country=DE --industrial-areas --sync')->weeklyOn(0, '01:00')->withoutOverlapping(720);
        $schedule->command('companies:discover gleif --gleif-cities --gleif-country=AE --gleif-pages=10')->monthlyOn(2, '02:30')->withoutOverlapping(1440);
        $schedule->command('market:data fetch-metals')->dailyAt('10:30')->timezone('Asia/Kolkata')->withoutOverlapping(30);
        $schedule->job(new \App\Jobs\WarmSectorGroups, 'catalog', 'database')->cron('0 */5 * * *');
        $schedule->command('discovery:bootstrap')->dailyAt('01:15')->withoutOverlapping(240);
        $schedule->command('technologies:sync --skip-discovery')->dailyAt('03:00')->withoutOverlapping(120);
        $schedule->command('jobs:role-counts')->everyThreeHours()->withoutOverlapping(30);
        $schedule->command('recommendations:build-skill-graph')->dailyAt('03:30')->withoutOverlapping(120);
        $schedule->command('premium:build-recommendations')
            ->dailyAt('08:00')
            ->withoutOverlapping(30);
        $schedule->command('candidates:auto-apply')
            ->dailyAt('08:15')
            ->withoutOverlapping(30);
        $schedule->command('news:fetch')->hourly()->withoutOverlapping(30);
        // Latest Intelligence: every topic in config/industry_news.php, one queued job per topic.
        $schedule->command('news:fetch-industry --industry=all --limit=10 --queue')->everySixHours()->withoutOverlapping(60);
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
