<?php

namespace App\Listeners;

use App\Events\ExternalJobsImported;
use App\Models\BatchRun;
use App\Services\Kafka\KafkaPublisher;
use App\Services\SectorDirectory;

class FinalizeExternalJobsImport
{
    public function handle(ExternalJobsImported $event): void
    {
        $run = BatchRun::find($event->batchRunId);
        if (!$run) return;
        $run->update(['status' => $run->failed_items > 0 ? 'partially_failed' : 'completed', 'pending_items' => 0, 'finished_at' => now()]);
        SectorDirectory::flush();
        app(KafkaPublisher::class)->publish(config('kafka.topics.job_ingestion'), 'jobs.feeds.imported', [
            'batch_run_id' => $run->id, 'found' => $run->records_found, 'created' => $run->records_created, 'updated' => $run->records_updated,
        ]);
    }
}
