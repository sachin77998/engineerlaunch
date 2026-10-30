<?php

namespace App\Console\Commands;

use App\Models\Technology;
use App\Services\SectorDirectory;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Builds the cached data behind the public pages before a visitor needs it: after a deploy or cache clear the first
 * visitor of a page would otherwise wait for every count and list on it. Each page is rendered in-process as a guest
 * (the "crawler" user agent keeps these out of the visitor statistics).
 */
class WarmCaches extends Command
{
    protected $signature = 'cache:warm {--only= : Comma-separated paths instead of the standard list}';
    protected $description = 'Render the public pages once so their cached data is ready for visitors';

    public function handle(SectorDirectory $sectors): int
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(0);
        // The category cards are built here (pages only ever show the last built copy while the queue rebuilds them).
        $this->time('sector cards', fn () => $sectors->groups());
        foreach ($this->paths() as $path) $this->time($path, fn () => $this->render($path));
        return self::SUCCESS;
    }

    private function paths(): array
    {
        if ($only = $this->option('only')) return array_filter(array_map('trim', explode(',', $only)));
        $paths = ['/', '/sectors', '/companies', '/career-explorer', '/industrial-areas', '/company-insights', '/technologies', '/latest', '/about',
            '/company-insights/explore/listed', '/company-insights/explore/companies', '/company-insights/explore/us', '/company-insights/explore/people',
            '/trading', '/trading/mutual-funds', '/trading/stocks', '/trading/etfs', '/money/funds', '/markets/us', '/market'];
        // The technologies people open most (their job lists are the heaviest pages).
        foreach (rescue(fn () => Technology::orderByDesc('jobs_count')->limit(40)->pluck('slug')->all(), [], false) as $slug) $paths[] = '/technologies/' . $slug;
        foreach (rescue(fn () => app(SectorDirectory::class)->groups()->pluck('slug')->all(), [], false) as $slug) $paths[] = '/sectors/category/' . $slug;
        // Recent news articles (each looks up related openings).
        foreach (rescue(fn () => DB::table('news_articles')->where('is_published', true)->latest('published_at')->limit(20)->pluck('slug')->all(), [], false) as $slug) $paths[] = '/latest/' . $slug;
        return $paths;
    }

    private function render(string $path): string
    {
        $request = Request::create($path, 'GET', [], [], [], ['HTTP_ACCEPT' => 'text/html', 'HTTP_USER_AGENT' => 'PortalCacheWarm crawler', 'REMOTE_ADDR' => '127.0.0.1']);
        $kernel = app(HttpKernel::class);
        app()->instance('request', $request);
        // Not terminated: the application runs every terminating callback registered so far on each terminate.
        return (string) $kernel->handle($request)->getStatusCode();
    }

    private function time(string $label, \Closure $work): void
    {
        $started = microtime(true);
        try {
            $result = $work();
            $this->line(sprintf('%-60s %5.1fs %s', $label, microtime(true) - $started, is_string($result) ? $result : 'ok'));
        } catch (\Throwable $e) {
            report($e);
            $this->warn(sprintf('%-60s failed: %s', $label, $e->getMessage()));
        }
    }
}
