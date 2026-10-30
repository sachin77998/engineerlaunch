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
        $schedule->command('trading:sync --holidays --navs --margins --instruments --catalogue --allot')->dailyAt('22:30')->timezone('Asia/Kolkata')->withoutOverlapping(60);
        $schedule->command('trading:sync --navs --allot')->dailyAt('08:30')->timezone('Asia/Kolkata')->withoutOverlapping(60);
        $schedule->command('trading:sync --returns --catalogue')->weeklyOn(0, '06:00')->timezone('Asia/Kolkata')->withoutOverlapping(60);
        $schedule->command('trading:eod-export')->weekdays()->at('23:45')->timezone('Asia/Kolkata')->withoutOverlapping(30);
        // NSE-listed companies: audited results, shareholding and prices weekly (only companies not synced in a week); prices daily.
        $schedule->command('insights:import-nse')->weeklyOn(6, '21:00')->timezone('Asia/Kolkata')->withoutOverlapping(120);
        // Every listing gets a company page and sector without waiting for its results to download.
        $schedule->command('insights:import-nse --link')->weeklyOn(0, '06:30')->timezone('Asia/Kolkata')->withoutOverlapping(60);
        $schedule->command('insights:import-nse --prices')->weekdays()->at('19:30')->timezone('Asia/Kolkata')->withoutOverlapping(30);
        // US stocks: closing quotes after the New York close; listings and SEC annual results weekly.
        $schedule->command('insights:import-us --prices')->weekdays()->at('17:30')->timezone('America/New_York')->withoutOverlapping(30);
        $schedule->command('insights:import-us')->weeklyOn(0, '09:00')->timezone('Asia/Kolkata')->withoutOverlapping(120);
        // Credit ratings: ICRA publishes rationales through the day; CARE company lookups nightly; older ICRA history read in daily slices.
        $schedule->command('insights:ratings --icra --limit=300')->cron('30 10,15,21 * * *')->timezone('Asia/Kolkata')->withoutOverlapping(90);
        $schedule->command('insights:ratings --care --care-limit=150')->dailyAt('02:30')->timezone('Asia/Kolkata')->withoutOverlapping(180);
        $schedule->command('insights:ratings --icra --limit=1 --backfill=300')->dailyAt('03:15')->timezone('Asia/Kolkata')->withoutOverlapping(180);
        // Similar-company recommender (content, financial profile and viewed-together signals).
        $schedule->command('insights:similar')->dailyAt('04:30')->timezone('Asia/Kolkata')->withoutOverlapping(120);
        // Funding events for rating reports read before the extractor existed, then the credit outlook model retrains.
        $schedule->command('insights:ratings --reparse --missing --batch=200')->dailyAt('01:30')->timezone('Asia/Kolkata')->withoutOverlapping(120);
        $schedule->command('insights:credit-model')->weeklyOn(0, '05:30')->timezone('Asia/Kolkata')->withoutOverlapping(60);
        // Stored credit assessments, ratio values, funding events, borrowing capacity and sector medians (stale ones first).
        // AMC monthly portfolios are published by the 10th: fetch them and recompute fund credit quality.
        $schedule->command('mf:portfolios')->monthlyOn(12, '04:00')->timezone('Asia/Kolkata')->withoutOverlapping(120);
        $schedule->command('mf:portfolios')->monthlyOn(20, '04:00')->timezone('Asia/Kolkata')->withoutOverlapping(120);
        $schedule->command('insights:assess --limit=1500')->dailyAt('02:30')->timezone('Asia/Kolkata')->withoutOverlapping(180);
        $schedule->command('logos:sync --limit=600')->dailyAt('03:45')->timezone('Asia/Kolkata')->withoutOverlapping(120);
        // Fund model: NAV history in daily batches, rescored every weekday after NAVs publish, retrained weekly.
        $schedule->command('money:funds --fetch=250 --score')->weekdays()->at('23:15')->timezone('Asia/Kolkata')->withoutOverlapping(120);
        $schedule->command('money:funds --fetch=0 --train --score')->weeklyOn(6, '05:00')->timezone('Asia/Kolkata')->withoutOverlapping(120);
        // State approvals from state portals and NSWS (all states, ~2 hours), checklists re-parsed only when changed.
        $schedule->command('approvals:fetch')->monthlyOn(8, '01:00')->timezone('Asia/Kolkata')->withoutOverlapping(480);
        // Re-reads the register monthly for new incorporations and status changes.
        $schedule->command('insights:import-mca')->monthlyOn(5, '00:30')->withoutOverlapping(60);
        $schedule->command('jobs:import-feeds')->everySixHours()->withoutOverlapping(120);
        $schedule->command('companies:discover all --country=IN --country=US --country=GB --country=DE --country=FR --country=CH --country=JP --country=AE --country=SG --country=CA --country=AU --country=NL --country=KR --country=CN --country=IT --country=ES --country=SE --country=BR --country=MX --country=QA --country=SA --country=IL --country=TW --country=ZA --industrial-areas --sync')->weeklyOn(0, '01:00')->withoutOverlapping(1440);
        $schedule->command('companies:discover gleif --gleif-cities --gleif-country=AE --gleif-pages=10')->monthlyOn(2, '02:30')->withoutOverlapping(1440);
        // Every registered company in the world whose name carries a trade word (gears, axles, forging, software, ...).
        $schedule->command('companies:discover gleif --gleif-trades --trade-country=WORLD')->monthlyOn(15, '01:30')->withoutOverlapping(1440);
        $schedule->command('market:data fetch-metals')->dailyAt('10:30')->timezone('Asia/Kolkata')->withoutOverlapping(30);
        // Insights dashboard: index levels, movers and database counts stay cached, so no visitor waits for them.
        $schedule->call(fn () => rescue(fn () => app(\App\Services\Insights\InsightsHome::class)->build()))->everyTenMinutes()->name('insights-home-warm')->withoutOverlapping(10);
        // Gold, silver and copper price history for the calculators (last 5 years, refreshed daily).
        $schedule->command('market:data metal-history')->dailyAt('07:00')->timezone('Asia/Kolkata')->withoutOverlapping(30);
        $schedule->job(new \App\Jobs\WarmSectorGroups, 'catalog', 'database')->cron('0 */5 * * *');
        $schedule->command('discovery:bootstrap')->dailyAt('01:15')->withoutOverlapping(240);
        $schedule->command('technologies:sync --skip-discovery')->dailyAt('03:00')->withoutOverlapping(120);
        // Job location matching (career explorer, job filters) reads this; rebuilding it takes about a minute.
        $schedule->command('jobs:refresh-places')->cron('*/45 * * * *')->withoutOverlapping(30);
        // Job search reads job_search: imported and changed jobs every 5 minutes (jobs saved through the app at once), all nightly.
        $schedule->command('jobs:search-index')->everyFiveMinutes()->withoutOverlapping(30);
        $schedule->command('jobs:search-index --full')->dailyAt('04:45')->withoutOverlapping(60);
        // Public pages' counts and lists are rebuilt here, not on a visitor's page view (also after every deploy).
        $schedule->command('cache:warm')->everyFifteenMinutes()->withoutOverlapping(30);
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
