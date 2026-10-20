<?php

namespace App\Http\Controllers;

/** Owner-only view of the last cPanel deployment run (written by scripts/cpanel-deploy.sh) and the latest application errors. */
class AdminDeployLogController extends Controller
{
    public function __invoke()
    {
        $path = storage_path('logs/deploy.log');
        $text = is_file($path) ? file_get_contents($path) : 'No deployment log yet. It is written on the next cPanel deployment.';
        return response($text . "\n\n==== Latest application errors ====\n" . $this->recentErrors(), 200, ['Content-Type' => 'text/plain; charset=utf-8', 'Cache-Control' => 'no-store']);
    }

    /** The last few exceptions of the newest Laravel log, without stack frames from vendor code. */
    private function recentErrors(int $limit = 5): string
    {
        $logs = glob(storage_path('logs/laravel*.log')) ?: [];
        usort($logs, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        if (!$logs) return 'No application log.';
        $handle = fopen($logs[0], 'r');
        fseek($handle, max(0, filesize($logs[0]) - 400000));
        $tail = stream_get_contents($handle);
        fclose($handle);
        preg_match_all('/^\[\d{4}-\d{2}-\d{2} [\d:]+\] \w+\.ERROR: .*?(?=^\[\d{4}-\d{2}-\d{2} |\z)/ms', $tail, $m);
        // Exceptions only (they carry a stack trace); routine messages such as failed job discovery are skipped.
        $entries = array_slice(array_values(array_filter($m[0], fn ($e) => str_contains($e, '[stacktrace]'))), -$limit);
        if (!$entries) return 'No errors in the latest log.';
        return implode("\n", array_map(fn ($entry) => implode("\n", array_slice(array_filter(explode("\n", $entry), fn ($line) => !str_contains($line, '/vendor/')), 0, 25)), $entries));
    }
}
