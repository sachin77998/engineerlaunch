<?php

namespace App\Listeners;

use App\Events\CompanyCatalogImported;
use App\Models\BatchRun;
use App\Services\Kafka\KafkaPublisher;
use App\Services\SectorDirectory;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class FinalizeCompanyCatalogImport
{
    public function handle(CompanyCatalogImported $event): void
    {
        SectorDirectory::flush();
        foreach (['company-discovery:facets:v3', 'company-discovery:countries:v3'] as $key) Cache::forget($key);

        $run = $event->batchRunId ? BatchRun::find($event->batchRunId) : null;
        $totals = $event->totals ?: ($run ? [
            'companies' => $run->records_found, 'created' => $run->records_created, 'updated' => $run->records_updated,
        ] : []);
        $run?->update(['status' => $run->failed_items > 0 ? 'partially_failed' : 'completed', 'finished_at' => now(), 'pending_items' => 0]);

        app(KafkaPublisher::class)->publish(config('kafka.topics.company_catalog'), 'company.catalog.imported', $totals + ['batch_run_id' => $event->batchRunId]);

        // Companies with official websites are now sync-enabled; fetch their latest openings in one batch.
        if ($event->syncOpenings) Artisan::call('jobs:dispatch-daily', ['--triggered-by' => 'catalog']);
    }
}
