<?php

namespace App\Jobs;

use App\Models\BatchRun;
use App\Services\Discovery\DiscoveryWriter;
use App\Services\Discovery\OpenStreetMapAreaSource;
use App\Services\Discovery\WikidataCompanySource;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

/** One unit of dynamic company discovery: a Wikidata country page or one OpenStreetMap hub. */
class DiscoverCompaniesChunk implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;
    public array $backoff = [60, 300];

    public function __construct(public string $source, public array $params, public ?int $batchRunId = null) {}

    public function handle(DiscoveryWriter $writer): void
    {
        if ($this->batch()?->cancelled()) return;
        if ($this->source === 'wikidata') {
            $companies = app(WikidataCompanySource::class)->fetch($this->params['qid'], $this->params['country'], $this->params['offset'], $this->params['limit'], $this->params['type'] ?? 'Q783794');
            $result = $writer->write($companies);
        } else {
            $companies = app(OpenStreetMapAreaSource::class)->fetch($this->params);
            $result = $writer->write($companies, $this->params);
            sleep(2); // keep the shared Overpass instance happy between hubs
        }
        if ($this->batchRunId) {
            BatchRun::whereKey($this->batchRunId)->incrementEach([
                'successful_items' => 1, 'records_found' => $result['companies'],
                'records_created' => $result['created'], 'records_updated' => $result['updated'],
            ], ['pending_items' => DB::raw('GREATEST(pending_items - 1, 0)')]);
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($this->batchRunId) {
            BatchRun::whereKey($this->batchRunId)->increment('failed_items', 1, [
                'failure_stage' => $this->source . ':' . ($this->params['name'] ?? $this->params['country'] ?? ''),
                'failure_reason' => mb_substr($exception->getMessage(), 0, 1000),
            ]);
        }
    }
}
