<?php

namespace App\Jobs;

use App\Models\BatchRun;
use App\Services\SectorCatalogImporter;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ImportSectorCatalogChunk implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    // Two sectors can share a company (ITC, Reliance); a retry resolves a concurrent first insert.
    public array $backoff = [5, 30];

    public function __construct(public int $sectorIndex, public ?int $batchRunId = null) {}

    public function handle(SectorCatalogImporter $importer): void
    {
        if ($this->batch()?->cancelled()) return;
        $sector = $importer->sectors()[$this->sectorIndex] ?? null;
        if (!$sector) return;
        $result = $importer->importSector($sector, $this->sectorIndex);
        if ($this->batchRunId) {
            BatchRun::whereKey($this->batchRunId)->incrementEach([
                'successful_items' => 1,
                'records_found' => $result['companies'],
                'records_created' => $result['created'],
                'records_updated' => $result['updated'],
            ], ['pending_items' => DB::raw('GREATEST(pending_items - 1, 0)')]);
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($this->batchRunId) {
            BatchRun::whereKey($this->batchRunId)->increment('failed_items', 1, [
                'failure_stage' => 'sector_' . $this->sectorIndex, 'failure_reason' => $exception->getMessage(),
            ]);
        }
    }
}
