<?php

namespace App\Console\Commands;

use App\Events\CompanyCatalogImported;
use App\Jobs\DiscoverCompaniesChunk;
use App\Models\BatchRun;
use App\Models\CompanyFacility;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Throwable;

class DiscoverCompanies extends Command
{
    protected $signature = 'companies:discover
        {source=all : wikidata, osm or all}
        {--country=* : ISO codes scanned on Wikidata (default IN)}
        {--pages=2 : Wikidata pages of 3000 rows per country and company type}
        {--area=* : Hub names from config/discovery.php (default: every hub)}
        {--with-plant-areas : Also scan industrial areas recorded on company plants (geocoded)}
        {--sync : Fetch openings for newly discovered company websites when finished}
        {--now : Run inline instead of on the queue}';
    protected $description = 'Discover companies dynamically from Wikidata and OpenStreetMap and file them into sectors';

    public function handle(): int
    {
        $source = $this->argument('source');
        if (!in_array($source, ['wikidata', 'osm', 'all'], true)) { $this->error('Source must be wikidata, osm or all.'); return self::INVALID; }
        $jobs = [];
        if ($source !== 'osm') {
            $countries = config('discovery.countries');
            foreach ($this->option('country') ?: ['IN'] as $code) {
                $country = $countries[strtoupper($code)] ?? null;
                if (!$country) { $this->warn("Unknown country {$code}; add it to config/discovery.php."); continue; }
                foreach (\App\Services\Discovery\WikidataCompanySource::TYPES as $type) {
                    for ($page = 0; $page < max(1, (int) $this->option('pages')); $page++) {
                        $jobs[] = ['wikidata', ['qid' => $country['qid'], 'country' => $country['name'], 'type' => $type, 'offset' => $page * 3000, 'limit' => 3000]];
                    }
                }
            }
        }
        if ($source !== 'wikidata') {
            foreach ($this->hubs() as $hub) $jobs[] = ['osm', $hub];
        }
        if (!$jobs) { $this->warn('Nothing to discover.'); return self::FAILURE; }

        $run = BatchRun::create([
            'batch_type' => 'company_discovery', 'name' => "Company discovery ({$source}) " . now()->toDateTimeString(),
            'status' => 'dispatching', 'triggered_by' => app()->runningInConsole() ? 'console' : 'scheduler', 'command' => 'companies:discover',
            'total_items' => count($jobs), 'pending_items' => count($jobs), 'started_at' => now(),
        ]);
        $runId = $run->id;
        $sync = (bool) $this->option('sync');
        $chunks = array_map(fn ($job) => new DiscoverCompaniesChunk($job[0], $job[1], $runId), $jobs);

        if ($this->option('now')) {
            $bar = $this->output->createProgressBar(count($chunks));
            foreach ($chunks as $chunk) {
                try { app()->call([$chunk, 'handle']); } catch (Throwable $e) { $chunk->failed($e); $this->newLine(); $this->warn($e->getMessage()); }
                $bar->advance();
            }
            $bar->finish(); $this->newLine();
            event(new CompanyCatalogImported($runId, $sync));
            $run->refresh();
            $this->info("Found {$run->records_found} companies ({$run->records_created} new) from {$run->successful_items}/{$run->total_items} sources.");
            return self::SUCCESS;
        }
        try {
            // One worker on this queue processes chunks in order, which respects the public APIs' rate limits.
            $batch = Bus::batch($chunks)->name($run->name)->allowFailures()
                ->finally(fn (Batch $batch) => event(new CompanyCatalogImported($runId, $sync)))
                ->onConnection('database')->onQueue('discovery')->dispatch();
        } catch (Throwable $exception) {
            $run->update(['status' => 'failed', 'failure_stage' => 'dispatch', 'failure_reason' => $exception->getMessage(), 'finished_at' => now()]);
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
        $run->update(['queue_batch_id' => $batch->id, 'status' => 'queued']);
        $this->info('Dispatched ' . count($chunks) . " discovery chunks (batch_runs.id={$runId}).");
        $this->line('Process with: php artisan queue:work database --queue=discovery --stop-when-empty');
        return self::SUCCESS;
    }

    private function hubs(): array
    {
        $wanted = array_map('mb_strtolower', $this->option('area'));
        $hubs = collect(config('discovery.hubs'))->when($wanted, fn ($c) => $c->filter(fn ($hub) => collect($wanted)->contains(fn ($w) => str_contains(mb_strtolower($hub['name']), $w))));
        if ($this->option('with-plant-areas')) {
            $known = $hubs->pluck('name')->map(fn ($n) => mb_strtolower($n));
            $plantAreas = CompanyFacility::whereNotNull('industrial_area')->select('industrial_area', 'city', 'state')->distinct()->get()
                ->reject(fn ($f) => $known->contains(mb_strtolower($f->industrial_area)))
                ->map(fn ($f) => ['name' => $f->industrial_area, 'city' => $f->city, 'state' => $f->state]);
            $hubs = $hubs->concat($plantAreas);
        }
        return $hubs->values()->all();
    }
}
