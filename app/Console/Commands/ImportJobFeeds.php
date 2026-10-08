<?php

namespace App\Console\Commands;

use App\Events\ExternalJobsImported;
use App\Jobs\FetchJobFeedChunk;
use App\Models\BatchRun;
use App\Services\JobFeeds\JobFeeds;
use App\Services\SectorCatalogImporter;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Throwable;

class ImportJobFeeds extends Command
{
    protected $signature = 'jobs:import-feeds
        {--feed=* : arbeitnow, remotive, adzuna, jooble (default: every configured feed)}
        {--queries=60 : Maximum searches per keyed feed (Adzuna, Jooble)}
        {--where= : Optional location for Adzuna/Jooble searches, e.g. Ludhiana}
        {--now : Run inline instead of on the queue}';
    protected $description = 'Fetch the latest openings for technology and industrial roles from public job feeds';

    public function handle(JobFeeds $feeds, SectorCatalogImporter $catalog): int
    {
        $selected = $this->option('feed') ?: $feeds->available();
        $missing = array_diff($selected, $feeds->available());
        foreach ($missing as $feed) $this->warn("Skipping {$feed}: add its API key to .env (see config/services.php).");
        $selected = array_values(array_diff($selected, $missing));

        $technology = config('discovery.technology_queries', []);
        $industrial = collect($catalog->sectors())->pluck('roles')->flatten()->unique()->values()->all();
        $limit = max(1, (int) $this->option('queries'));
        $where = $this->option('where');
        $chunks = [];
        foreach ($selected as $feed) {
            $requests = match ($feed) {
                'arbeitnow' => array_map(fn ($page) => ['page' => $page], range(1, 3)),
                'remotive' => array_map(fn ($query) => ['query' => $query], array_slice($technology, 0, 12)),
                // Interleave industrial and technology roles so a small limit still covers both.
                default => array_map(fn ($query) => array_filter(['query' => $query, 'where' => $where]), array_slice($this->interleave($industrial, $technology), 0, $limit)),
            };
            foreach ($requests as $params) $chunks[] = [$feed, $params];
        }
        if (!$chunks) { $this->warn('No job feeds selected.'); return self::FAILURE; }

        $run = BatchRun::create([
            'batch_type' => 'job_feed_import', 'name' => 'Job feeds (' . implode(', ', $selected) . ') ' . now()->toDateTimeString(),
            'status' => 'dispatching', 'triggered_by' => 'console', 'command' => 'jobs:import-feeds',
            'total_items' => count($chunks), 'pending_items' => count($chunks), 'started_at' => now(),
        ]);
        $runId = $run->id;
        $jobs = array_map(fn ($chunk) => new FetchJobFeedChunk($chunk[0], $chunk[1], $runId), $chunks);

        if ($this->option('now')) {
            $bar = $this->output->createProgressBar(count($jobs));
            foreach ($jobs as $job) {
                try { app()->call([$job, 'handle']); } catch (Throwable $e) { $job->failed($e); }
                $bar->advance();
            }
            $bar->finish(); $this->newLine();
            event(new ExternalJobsImported($runId));
            $run->refresh();
            $this->info("Feeds returned {$run->records_found} openings ({$run->records_created} new); {$run->failed_items} requests failed.");
            return self::SUCCESS;
        }
        try {
            $batch = Bus::batch($jobs)->name($run->name)->allowFailures()
                ->finally(fn (Batch $batch) => event(new ExternalJobsImported($runId)))
                ->onConnection('database')->onQueue('feeds')->dispatch();
        } catch (Throwable $exception) {
            $run->update(['status' => 'failed', 'failure_stage' => 'dispatch', 'failure_reason' => $exception->getMessage(), 'finished_at' => now()]);
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
        $run->update(['queue_batch_id' => $batch->id, 'status' => 'queued']);
        $this->info('Dispatched ' . count($jobs) . " feed requests (batch_runs.id={$runId}).");
        return self::SUCCESS;
    }

    private function interleave(array $a, array $b): array
    {
        $out = [];
        for ($i = 0; $i < max(count($a), count($b)); $i++) {
            if (isset($a[$i])) $out[] = $a[$i];
            if (isset($b[$i])) $out[] = $b[$i];
        }
        return array_values(array_unique($out));
    }
}
