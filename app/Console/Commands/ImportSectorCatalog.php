<?php

namespace App\Console\Commands;

use App\Events\CompanyCatalogImported;
use App\Jobs\ImportSectorCatalogChunk;
use App\Models\BatchRun;
use App\Services\SectorCatalogImporter;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Throwable;

class ImportSectorCatalog extends Command
{
    protected $signature = 'companies:import-catalog
        {--sync : Dispatch the official opening batch (jobs:dispatch-daily) after the import}
        {--now : Import inline instead of through the queue}';
    protected $description = 'Import the sector -> subsector -> company catalog (brands, plants) as a queued batch';

    public function handle(SectorCatalogImporter $importer): int
    {
        $sectors = $importer->sectors();
        if (!$sectors) { $this->warn('No catalog files found in resources/data/sector-catalog.'); return self::FAILURE; }
        $sync = (bool) $this->option('sync');

        if ($this->option('now')) {
            $totals = $importer->importAll();
            event(new CompanyCatalogImported(null, $sync, $totals));
            $this->table(array_keys($totals), [array_values($totals)]);
            return self::SUCCESS;
        }

        $run = BatchRun::create([
            'batch_type' => 'company_catalog_import', 'name' => 'Sector company catalog import ' . now()->toDateTimeString(),
            'status' => 'dispatching', 'triggered_by' => 'console', 'command' => 'companies:import-catalog',
            'host' => gethostname() ?: null, 'process_id' => getmypid() ?: null,
            'total_items' => count($sectors), 'pending_items' => count($sectors), 'started_at' => now(),
        ]);
        $runId = $run->id;
        $jobs = array_map(fn ($index) => new ImportSectorCatalogChunk($index, $runId), array_keys($sectors));
        try {
            $batch = Bus::batch($jobs)->name($run->name)->allowFailures()
                ->finally(fn (Batch $batch) => event(new CompanyCatalogImported($runId, $sync)))
                ->onConnection('database')->onQueue('catalog')->dispatch();
        } catch (Throwable $exception) {
            $run->update(['status' => 'failed', 'failure_stage' => 'dispatch', 'failure_reason' => $exception->getMessage(), 'finished_at' => now()]);
            $this->error('Batch dispatch failed: ' . $exception->getMessage());
            return self::FAILURE;
        }
        $run->update(['queue_batch_id' => $batch->id, 'status' => 'queued']);
        $this->info("Dispatched catalog batch {$batch->id} with " . count($jobs) . ' sectors (batch_runs.id=' . $runId . ').');
        $this->line('Process it with: php artisan queue:work database --queue=catalog --stop-when-empty');
        return self::SUCCESS;
    }
}
