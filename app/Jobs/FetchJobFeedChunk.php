<?php

namespace App\Jobs;

use App\Models\BatchRun;
use App\Services\JobFeeds\ExternalJobWriter;
use App\Services\JobFeeds\JobFeeds;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

/** One request to a public job feed (a page or a search query) and its upsert into jobs. */
class FetchJobFeedChunk implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;
    public array $backoff = [30, 120];

    public function __construct(public string $feed, public array $params, public ?int $batchRunId = null) {}

    public function handle(JobFeeds $feeds, ExternalJobWriter $writer): void
    {
        if ($this->batch()?->cancelled()) return;
        $result = $writer->write($feeds->fetch($this->feed, $this->params));
        if ($this->batchRunId) {
            BatchRun::whereKey($this->batchRunId)->incrementEach([
                'successful_items' => 1, 'records_found' => $result['found'],
                'records_created' => $result['created'], 'records_updated' => $result['updated'],
            ], ['pending_items' => DB::raw('GREATEST(pending_items - 1, 0)')]);
        }
        usleep(400000);
    }

    public function failed(Throwable $exception): void
    {
        if ($this->batchRunId) {
            BatchRun::whereKey($this->batchRunId)->increment('failed_items', 1, [
                'failure_stage' => $this->feed . ':' . ($this->params['query'] ?? $this->params['page'] ?? ''),
                'failure_reason' => mb_substr($exception->getMessage(), 0, 1000),
            ]);
        }
    }
}
