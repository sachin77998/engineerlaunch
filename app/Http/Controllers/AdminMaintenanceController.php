<?php

namespace App\Http\Controllers;

use App\Jobs\RunMaintenanceStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Owner-only "Production Setup" page: runs the whitelisted artisan commands on the live server
 * from the browser (GoDaddy shared hosting has no SSH for this account), one step per request.
 */
class AdminMaintenanceController extends Controller
{
    public const STEPS = [
        'env' => ['Write missing .env settings', 'env:ensure', []],
        'clear' => ['Clear caches', 'optimize:clear', []],
        'prepare' => ['Prepare career tables', 'portal:prepare-discovery', []],
        'migrate' => ['Run database migrations', 'migrate', ['--force' => true]],
        'catalog' => ['Import sector company catalog', 'companies:import-catalog', ['--now' => true]],
        'reclassify' => ['Re-sort discovered companies', 'companies:reclassify', ['--all' => true]],
        'feeds' => ['Fetch latest job feeds', 'jobs:import-feeds', ['--now' => true, '--queries' => 20]],
        'wikidata' => ['Discover Indian companies (Wikidata)', 'companies:discover', ['source' => 'wikidata', '--country' => ['IN'], '--pages' => 1, '--now' => true]],
        'osm' => ['Queue discovery for every industrial area (OpenStreetMap)', 'companies:discover', ['source' => 'osm', '--industrial-areas' => true]],
        'gleif' => ['Queue registered companies for every city (GLEIF registry, India + UAE)', 'companies:discover', ['source' => 'gleif', '--gleif-cities' => true, '--gleif-country' => ['AE'], '--gleif-pages' => 10]],
        'gleif-trades' => ['Queue registered companies by trade (forging, casting, auto, steel, pharma, textiles… across India)', 'companies:discover', ['source' => 'gleif', '--gleif-trades' => true]],
        'gleif-india' => ['Queue every registered company in India (GLEIF, ~400,000; imports over several hours)', 'companies:discover', ['source' => 'gleif', '--gleif-all' => ['IN']]],
        'metals' => ['Fetch today\'s gold and silver prices (needs GOLDAPI_KEY)', 'market:data', ['action' => 'fetch-metals']],
        'news' => ['Refresh Latest Intelligence news (all topics; queued, also runs every 6 hours)', 'news:fetch-industry', ['--industry' => 'all', '--limit' => 10, '--queue' => true]],
        'parks' => ['Queue every IT park, SEZ and industrial area in all states (OpenStreetMap)', 'companies:discover', ['source' => 'parks']],
        'technologies' => ['Discover every language, framework and tool and match jobs to them', 'technologies:sync', []],
        'status' => ['Show migration status', 'migrate:status', []],
        'https' => ['Enable HTTPS (only after the SSL certificate is active)', 'security:https', ['--enable' => true]],
    ];

    // Quick steps run inside the browser request; every other step is queued and finished by the cron queue worker.
    public const INLINE = ['env', 'clear', 'prepare', 'migrate', 'status', 'https'];

    /** "Refresh everything" (one click each morning): module => [label => [command, arguments]]. All run on the queue. */
    public const REFRESH_MODULES = [
        'jobs' => ['Jobs', [
            'Company career pages (every verified feed)' => ['jobs:dispatch-daily', ['--triggered-by' => 'owner']],
            'Job boards (Arbeitnow, Remotive, Adzuna, Jooble)' => ['jobs:import-feeds', ['--now' => true, '--queries' => 20]],
            'Match industrial jobs to companies' => ['industrial:sync-jobs', ['--limit' => 2000]],
            'Classify jobs into categories' => ['jobs:classify', []],
        ]],
        'companies' => ['Companies & sectors', [
            'Research new companies: IT parks, registries, trades, brands, group subsidiaries (each when due)' => ['discovery:bootstrap', []],
            'Import sector catalog' => ['companies:import-catalog', []],
            'Re-sort unclassified companies into sectors' => ['companies:reclassify', []],
            'Rebuild Companies by Sector cards' => ['sectors:warm', []],
        ]],
        'news' => ['Latest in Tech', [
            'Fetch news sources' => ['news:fetch', []],
            'Fetch every industry topic' => ['news:fetch-industry', ['--industry' => 'all', '--limit' => 10, '--queue' => true]],
            'Process articles' => ['news:process', []],
            'Publish articles' => ['news:publish', []],
        ]],
        'learning' => ['Learning & technologies', [
            'Refresh every language, framework and tool with live job counts' => ['technologies:sync', ['--skip-discovery' => true]],
            'Rebuild skill graph for recommendations' => ['recommendations:build-skill-graph', []],
            'Refresh learning pages' => ['view:clear', []],
        ]],
        'market' => ['Market data', [
            'Gold and silver prices (needs GOLDAPI_KEY)' => ['market:data', ['action' => 'fetch-metals']],
        ]],
    ];

    // Seeders run one per request: running them all in one web request exceeds shared-hosting time limits.
    public const SEEDERS = [
        'OwnerAccountSeeder', 'TechnologySeeder', 'JobCategorySeeder', 'JobTitleSeeder', 'CompanySeeder', 'ItCompanyDirectorySeeder',
        'RequestedEmployerInventorySeeder', 'OfficialCareerSourceSeeder', 'RequestedCompanySourceSeeder', 'CyberCityCompanySeeder',
        'CareerExplorerSeeder', 'CompanyCategorySeeder', 'CompanyDiscoverySeeder', 'SectorCatalogSeeder', 'NewsIntelligenceSeeder',
        'IndustryNewsCategorySeeder', 'InterviewCompanySeeder', 'IndustrialDirectorySeeder', 'IndustrialRecruitmentSeeder',
    ];

    public static function steps(): array
    {
        $steps = [];
        foreach (self::STEPS as $key => $step) {
            $steps[$key] = $step;
            if ($key === 'catalog') {
                foreach (self::SEEDERS as $seeder) $steps['seed-' . $seeder] = ['Seed: ' . $seeder, 'db:seed', ['--class' => $seeder, '--force' => true]];
            }
        }
        return $steps;
    }

    public function index()
    {
        $log = storage_path('logs/deploy.log');
        $phpPaths = array_values(array_filter([
            '/opt/cpanel/ea-php84/root/usr/bin/php', '/opt/cpanel/ea-php83/root/usr/bin/php', '/opt/cpanel/ea-php82/root/usr/bin/php',
            '/opt/cpanel/ea-php81/root/usr/bin/php', '/opt/alt/php83/usr/bin/php', '/opt/alt/php82/usr/bin/php', '/usr/local/bin/php', '/usr/bin/php',
        ], fn ($path) => @is_executable($path)));
        return view('admin.maintenance', ['steps' => self::steps(), 'deployLog' => is_file($log) ? file_get_contents($log) : null,
            'phpPaths' => $phpPaths, 'phpVersion' => PHP_VERSION, 'basePath' => base_path(), 'repos' => $this->repositories()]);
    }

    /**
     * cPanel disables "Deploy HEAD Commit" while its repository clone has local changes or a deployment is still
     * running. These actions show that state and can reset the clone, or pull and deploy without the cPanel button.
     */
    public function repository(Request $request, int $index, string $action)
    {
        abort_unless(in_array($action, ['clean', 'deploy'], true), 404);
        $repo = $this->repositories()[$index]['path'] ?? abort(404);
        @set_time_limit(600);
        ignore_user_abort(true);
        $branch = trim($this->shell(['git', 'rev-parse', '--abbrev-ref', 'HEAD'], $repo)[1]) ?: 'main';
        $steps = [['git', 'fetch', 'origin', '--prune'], ['git', 'reset', '--hard', $action === 'deploy' ? 'origin/' . $branch : 'HEAD'], ['git', 'clean', '-fd']];
        $output = '';
        $exit = 0;
        foreach ($steps as $command) {
            [$code, $text] = $this->shell($command, $repo);
            $output .= '$ ' . implode(' ', $command) . "\n" . $text . "\n";
            $exit = $exit ?: $code;
        }
        if ($action === 'deploy' && $exit === 0) {
            $tasks = $this->deploymentTasks($repo);
            if (!$tasks) {
                $output .= "No deployment tasks found in .cpanel.yml\n";
                $exit = 1;
            } else {
                [$exit, $text] = $this->shell(['/bin/bash', '-c', implode("\n", $tasks)], $repo, 540);
                $output .= "$ .cpanel.yml tasks\n" . $text;
            }
        }
        return response()->json(['exit' => $exit, 'output' => mb_substr(trim($output), -12000)]);
    }

    /** Git clones made by cPanel's Git Version Control (~/repositories/NAME). */
    private function repositories(): array
    {
        $home = getenv('HOME') ?: (preg_match('~^(/home\d*/[^/]+)~', base_path(), $m) ? $m[1] : null);
        if (!$home || !function_exists('proc_open')) return [];
        $repos = [];
        foreach (glob($home . '/repositories/*', GLOB_ONLYDIR) ?: [] as $path) {
            if (!is_dir($path . '/.git')) continue;
            [$code, $status] = $this->shell(['git', 'status', '--porcelain'], $path);
            $repos[] = [
                'name' => basename($path), 'path' => $path, 'available' => $code === 0,
                'head' => trim($this->shell(['git', 'log', '-1', '--format=%h %s (%cr)'], $path)[1]) ?: null,
                'changes' => array_values(array_filter(explode("\n", trim($status)))),
                'has_cpanel_yml' => is_file($path . '/.cpanel.yml'),
            ];
        }
        return $repos;
    }

    private function deploymentTasks(string $repo): array
    {
        $tasks = [];
        $inTasks = false;
        foreach (@file($repo . '/.cpanel.yml') ?: [] as $line) {
            if (preg_match('/^\s*tasks:\s*$/', $line)) { $inTasks = true; continue; }
            if ($inTasks && preg_match('/^\s*-\s+(.+?)\s*$/', $line, $m)) $tasks[] = $m[1];
        }
        return $tasks;
    }

    /** @return array{0:int, 1:string} exit code and combined output */
    private function shell(array $command, string $cwd, int $timeout = 120): array
    {
        try {
            $process = new Process($command, $cwd, ['HOME' => getenv('HOME') ?: dirname($cwd, 2), 'GIT_TERMINAL_PROMPT' => '0'], null, $timeout);
            $process->run();
            return [$process->getExitCode() ?? 1, $process->getOutput() . $process->getErrorOutput()];
        } catch (Throwable $e) {
            return [1, $e->getMessage()];
        }
    }

    /** Queues one step (or every queued step with "all") for the background worker. */
    public function queue(string $step)
    {
        $steps = self::steps();
        $keys = $step === 'all' ? array_values(array_diff(array_keys($steps), self::INLINE, ['gleif-india', 'https'])) : [$step];
        abort_unless($step === 'all' || (isset($steps[$step]) && !in_array($step, self::INLINE, true)), 404);
        foreach ($keys as $key) {
            [$label, $command, $arguments] = $steps[$key];
            RunMaintenanceStep::markQueued($key, $label);
            RunMaintenanceStep::dispatch($key, $label, $command, $arguments);
        }
        return response()->json(['queued' => $keys]);
    }

    /** One click each morning: every module's refresh as a single batch on the queue. */
    public function refreshAll()
    {
        $jobs = [];
        foreach (self::REFRESH_MODULES as $module => [$moduleLabel, $tasks]) {
            foreach ($tasks as $label => [$command, $arguments]) {
                $key = 'refresh-' . $module . '-' . md5($label);
                RunMaintenanceStep::markQueued($key, $label);
                $jobs[] = new RunMaintenanceStep($key, $label, $command, $arguments);
            }
        }
        $batch = Bus::batch($jobs)->name('Refresh everything')->allowFailures()->onConnection('database')->onQueue('maintenance')->dispatch();
        Cache::put('maintenance-refresh', ['batch' => $batch->id, 'started_at' => now()->toDateTimeString()], now()->addDays(7));
        return response()->json(['batch' => $batch->id, 'jobs' => count($jobs)]);
    }

    /** States of queued steps and of the latest "Refresh everything" run, polled by the page. */
    public function status()
    {
        $steps = [];
        foreach (array_keys(self::steps()) as $key) {
            if ($state = Cache::get(RunMaintenanceStep::statusKey($key))) $steps[$key] = $state;
        }
        $modules = [];
        foreach (self::REFRESH_MODULES as $module => [$moduleLabel, $tasks]) {
            foreach ($tasks as $label => $task) {
                $modules[$module][] = ['label' => $label] + (Cache::get(RunMaintenanceStep::statusKey('refresh-' . $module . '-' . md5($label))) ?: ['state' => 'idle']);
            }
        }
        $refresh = Cache::get('maintenance-refresh');
        $batch = $refresh ? Bus::findBatch($refresh['batch']) : null;
        $pending = \Illuminate\Support\Facades\DB::table(config('queue.connections.database.table', 'jobs_queue'))->where('queue', 'maintenance')->count();
        return response()->json(['steps' => $steps, 'modules' => $modules, 'pending' => $pending,
            'refresh' => $refresh ? $refresh + ['progress' => $batch?->progress(), 'finished' => (bool) $batch?->finished(), 'failed' => $batch?->failedJobs] : null]);
    }

    public function run(Request $request, string $step)
    {
        $steps = self::steps();
        abort_unless(isset($steps[$step]), 404);
        [$label, $command, $arguments] = $steps[$step];
        // The browser knows the real domain; the server's APP_URL may still say localhost.
        if ($step === 'https') $arguments['--host'] = $request->getHost();
        if ($step === 'env') $arguments['--url'] = $request->getSchemeAndHttpHost();
        @set_time_limit(600);
        ignore_user_abort(true);
        $started = microtime(true);
        try {
            $exit = Artisan::call($command, $arguments);
            $output = Artisan::output();
        } catch (Throwable $e) {
            report($e);
            $exit = 1;
            $output = get_class($e) . ': ' . $e->getMessage();
        }
        return response()->json([
            'step' => $step, 'label' => $label, 'exit' => $exit,
            'seconds' => round(microtime(true) - $started, 1), 'output' => mb_substr(trim($output), -12000),
        ]);
    }
}
