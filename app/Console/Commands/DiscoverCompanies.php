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
        {source=all : wikidata, osm, gleif, parks, brands or all}
        {--country=* : ISO codes scanned on Wikidata (default IN)}
        {--pages=2 : Wikidata pages of 3000 rows per country and company type}
        {--area=* : Hub names from config/discovery.php (default: every hub)}
        {--with-plant-areas : Also scan industrial areas recorded on company plants (geocoded)}
        {--industrial-areas : Also scan every area in the industrial area directory (all cities)}
        {--gleif-cities : GLEIF registry: companies registered in every industrial-area / hub city (India)}
        {--gleif-country=* : GLEIF registry: whole countries by ISO code, e.g. AE GB DE}
        {--gleif-pages=10 : GLEIF pages of 200 companies per city or country}
        {--gleif-trades : GLEIF registry: every company whose name contains a trade word (config discovery.trade_terms)}
        {--trade-country=* : Countries for --gleif-trades by ISO code, or WORLD for every country (default IN)}
        {--trade-term=* : Only these trade words (default: every word in config discovery.trade_terms)}
        {--trade-pages=50 : Pages of 200 per trade word and country (cursor paging; GLEIF matches whole words)}
        {--gleif-all=* : GLEIF registry: every registered company of these countries (cursor paging, e.g. IN = ~400k)}
        {--state=* : parks: only these states (default: every state and union territory)}
        {--sync : Fetch openings for newly discovered company websites when finished}
        {--now : Run inline instead of on the queue}';
    protected $description = 'Discover companies dynamically from Wikidata and OpenStreetMap and file them into sectors';

    public function handle(): int
    {
        $source = $this->argument('source');
        if (!in_array($source, ['wikidata', 'osm', 'gleif', 'parks', 'brands', 'all'], true)) { $this->error('Source must be wikidata, osm, gleif, parks, brands or all.'); return self::INVALID; }
        $jobs = [];
        // Brands and chains trading in India: the Name Suggestion Index, then every branded outlet per state (OpenStreetMap).
        if (in_array($source, ['brands', 'all'], true)) {
            $jobs[] = ['nsi', []];
            foreach ($this->option('state') ?: array_keys(\App\Services\Discovery\ParkSource::STATES) as $state) $jobs[] = ['brands', ['state' => $state]];
        }
        // IT parks, SEZs and industrial areas of every state first, so the area scans below include them.
        if ($source === 'parks') {
            foreach ($this->option('state') ?: array_keys(\App\Services\Discovery\ParkSource::STATES) as $state) $jobs[] = ['parks', ['state' => $state]];
        }
        if (in_array($source, ['gleif', 'all'], true)) {
            $pages = range(1, max(1, (int) $this->option('gleif-pages')));
            if ($this->option('gleif-cities')) {
                foreach ($this->registryCities() as $city) foreach ($pages as $page) $jobs[] = ['gleif', ['country' => 'IN', 'city' => $city, 'page' => $page]];
            }
            foreach ($this->option('gleif-country') as $code) foreach ($pages as $page) $jobs[] = ['gleif', ['country' => strtoupper($code), 'page' => $page]];
            // One starting chunk per country; each chunk queues the next page (up to 5,000 pages = 1M records).
            if ($this->option('gleif-trades')) {
                // One chain per word and country: each chunk queues the next cursor page until the matches run out. Worldwide
                // scans use the narrower engineering / software word list; generic words ("solutions") would flood the catalogue.
                foreach ($this->option('trade-country') ?: ['IN'] as $code) foreach (array_values(array_unique($this->option('trade-term')
                    ?: config(strtoupper($code) === 'WORLD' ? 'discovery.world_trade_terms' : 'discovery.trade_terms', []))) as $term) {
                    $jobs[] = ['gleif', ['country' => strtoupper($code), 'term' => $term, 'page' => 1, 'cursor' => '*', 'pages_left' => max(1, (int) $this->option('trade-pages'))]];
                }
            }
            foreach ($this->option('gleif-all') as $code) $jobs[] = ['gleif', ['country' => strtoupper($code), 'page' => 1, 'cursor' => '*', 'pages_left' => 5000]];
        }
        if (!in_array($source, ['osm', 'gleif', 'parks', 'brands'], true)) {
            $countries = config('discovery.countries');
            foreach ($this->option('country') ?: ['IN'] as $code) {
                $country = $countries[strtoupper($code)] ?? null;
                if (!$country) { $this->warn("Unknown country {$code}; add it to config/discovery.php."); continue; }
                foreach (array_merge(\App\Services\Discovery\WikidataCompanySource::MODES, \App\Services\Discovery\WikidataCompanySource::TYPES) as $type) {
                    for ($page = 0; $page < max(1, (int) $this->option('pages')); $page++) {
                        $jobs[] = ['wikidata', ['qid' => $country['qid'], 'country' => $country['name'], 'type' => $type, 'offset' => $page * 3000, 'limit' => 3000]];
                    }
                }
            }
        }
        if (in_array($source, ['osm', 'all'], true)) {
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

    /** Every city that has an industrial area or a configured hub, one entry per spelling-normalised city. */
    private function registryCities(): array
    {
        $cities = collect(config('discovery.hubs'))->pluck('city')
            ->merge(\App\Models\IndustrialArea::where('is_active', true)->pluck('city'))
            ->filter()->map(fn ($c) => trim($c))->unique(fn ($c) => mb_strtolower($c));
        $wanted = array_map('mb_strtolower', $this->option('area'));
        return $cities->when($wanted, fn ($c) => $c->filter(fn ($city) => in_array(mb_strtolower($city), $wanted, true)))->values()->all();
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
        if ($this->option('industrial-areas')) {
            $known = $hubs->pluck('name')->map(fn ($n) => mb_strtolower($n));
            $areas = \App\Models\IndustrialArea::with('state:id,name')->where('is_active', true)->get()
                ->reject(fn ($area) => $known->contains(mb_strtolower($area->name)))
                ->when($wanted, fn ($c) => $c->filter(fn ($area) => collect($wanted)->contains(fn ($w) => str_contains(mb_strtolower($area->name . ' ' . $area->city), $w))))
                ->map(fn ($area) => array_filter([
                    'name' => $area->name, 'city' => $area->city ?: $area->district, 'state' => $area->state?->name, 'area_id' => $area->id,
                    'lat' => $area->latitude ? (float) $area->latitude : null, 'lon' => $area->longitude ? (float) $area->longitude : null,
                ], fn ($v) => $v !== null));
            $hubs = $hubs->concat($areas);
        }
        return $hubs->values()->all();
    }
}
