<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

/** One "Latest Intelligence" topic (config/industry_news.php) refreshed on the queue. */
class FetchIndustryNewsTopic implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(public string $topic, public int $limit = 10) {}

    public function handle(): void
    {
        Artisan::call('news:fetch-industry', ['--industry' => $this->topic, '--limit' => $this->limit]);
    }
}
