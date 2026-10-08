<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Runs during the cPanel deployment: adds missing non-secret settings to the server .env
 * and switches off debug output. Existing values (database, mail, keys) are never overwritten.
 */
class EnsureEnvironmentSettings extends Command
{
    protected $signature = 'env:ensure {--path= : .env file to update (default: the application .env)}';
    protected $description = 'Add missing deployment settings to .env and disable debug output';

    // The Jooble key is committed at the owner's request (public repo); regenerate it on jooble.org if misused.
    private const DEFAULTS = [
        'JOOBLE_API_KEY' => 'dac2da19-8a3b-4990-9760-21b60b377956',
        'JOOBLE_HOST' => 'jooble.org',
        'JOOBLE_COUNTRY' => '"United States"',
        'DISCOVERY_RADIUS' => '4500',
    ];

    // Values enforced on production regardless of what is currently set.
    private const ENFORCED = ['APP_DEBUG' => 'false'];

    public function handle(): int
    {
        $path = $this->option('path') ?: base_path('.env');
        if (!is_file($path) || !is_writable($path)) {
            $this->warn("Skipping: {$path} is missing or not writable.");
            return self::SUCCESS;
        }
        $original = file_get_contents($path);
        $contents = $original;
        $changed = [];

        foreach (self::ENFORCED as $key => $value) {
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
            if (preg_match($pattern, $contents, $match)) {
                if (trim($match[0]) !== "{$key}={$value}") { $contents = preg_replace($pattern, "{$key}={$value}", $contents); $changed[] = $key; }
            } else {
                $contents = rtrim($contents, "\r\n") . "\n{$key}={$value}\n"; $changed[] = $key;
            }
        }
        foreach (self::DEFAULTS as $key => $value) {
            if (!preg_match('/^' . preg_quote($key, '/') . '=/m', $contents)) {
                $contents = rtrim($contents, "\r\n") . "\n{$key}={$value}\n"; $changed[] = $key;
            }
        }

        if ($contents !== $original) {
            copy($path, $path . '.backup-' . date('YmdHis'));
            file_put_contents($path, $contents);
        }
        $this->info($changed ? 'Updated .env: ' . implode(', ', $changed) : '.env already up to date.');
        return self::SUCCESS;
    }
}
