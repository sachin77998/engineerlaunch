<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
