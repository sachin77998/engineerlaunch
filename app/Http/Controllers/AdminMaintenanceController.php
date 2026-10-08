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
        'seed' => ['Run all seeders', 'db:seed', ['--force' => true]],
        'reclassify' => ['Re-sort discovered companies', 'companies:reclassify', ['--all' => true]],
        'feeds' => ['Fetch latest job feeds', 'jobs:import-feeds', ['--now' => true, '--queries' => 20]],
        'wikidata' => ['Discover Indian companies (Wikidata)', 'companies:discover', ['source' => 'wikidata', '--country' => ['IN'], '--pages' => 1, '--now' => true]],
        'osm' => ['Queue industrial hub discovery (OpenStreetMap)', 'companies:discover', ['source' => 'osm']],
        'status' => ['Show migration status', 'migrate:status', []],
    ];

    public function index()
    {
        $log = storage_path('logs/deploy.log');
        return view('admin.maintenance', ['steps' => self::STEPS, 'deployLog' => is_file($log) ? file_get_contents($log) : null]);
    }

    public function run(Request $request, string $step)
    {
        abort_unless(isset(self::STEPS[$step]), 404);
        [$label, $command, $arguments] = self::STEPS[$step];
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
