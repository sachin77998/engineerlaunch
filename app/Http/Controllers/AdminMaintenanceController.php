<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
        'osm' => ['Queue industrial hub discovery (OpenStreetMap)', 'companies:discover', ['source' => 'osm']],
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
            'phpPaths' => $phpPaths, 'phpVersion' => PHP_VERSION, 'basePath' => base_path()]);
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
