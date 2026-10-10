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
        if ($this->source === 'gleif') {
            $page = app(\App\Services\Discovery\GleifCompanySource::class)->fetch($this->params['country'], $this->params['city'] ?? null, $this->params['page']);
            $result = app(\App\Services\Discovery\RegistryWriter::class)->write($page['rows']);
            usleep(500000);
        } elseif ($this->source === 'wikidata') {
            $companies = app(WikidataCompanySource::class)->fetch($this->params['qid'], $this->params['country'], $this->params['offset'], $this->params['limit'], $this->params['type'] ?? 'Q783794');
            $result = $writer->write($companies);
        } else {
            $source = app(OpenStreetMapAreaSource::class);
            // Directory areas have no stored coordinates yet: locate once and remember them.
            if (isset($this->params['area_id']) && !isset($this->params['lat'], $this->params['lon'])) {
                [$this->params['lat'], $this->params['lon']] = $source->locate($this->params);
                \App\Models\IndustrialArea::whereKey($this->params['area_id'])->update(['latitude' => $this->params['lat'], 'longitude' => $this->params['lon']]);
            }
            $companies = $source->fetch($this->params);
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
