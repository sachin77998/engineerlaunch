<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Runs during the cPanel deployment: adds missing non-secret settings to the server .env
 * and switches off debug output. Existing values (database, mail, keys) are never overwritten.
 */
class EnsureEnvironmentSettings extends Command
{
    protected $signature = 'env:ensure {--path= : .env file to update (default: the application .env)} {--url= : Public site URL; replaces a missing or localhost APP_URL}';
    protected $description = 'Add missing deployment settings to .env and disable debug output';

    // The Jooble key is committed at the owner's request (public repo); regenerate it on jooble.org if misused.
    private const DEFAULTS = [
        'JOOBLE_API_KEY' => 'dac2da19-8a3b-4990-9760-21b60b377956',
        'JOOBLE_HOST' => 'jooble.org',
        'JOOBLE_COUNTRY' => '"United States"',
        'DISCOVERY_RADIUS' => '4500',
        'APP_FORCE_HTTPS' => 'false',
    ];

    // Values enforced on production regardless of what is currently set.
    private const ENFORCED = ['APP_DEBUG' => 'false', 'APP_ENV' => 'production'];

    /** Copies .env into storage/app/env-backups; a backup next to .env counts as an uncommitted change for cPanel. */
    public static function backup(string $path): void
    {
        $dir = storage_path('app/env-backups');
        if (!is_dir($dir)) @mkdir($dir, 0750, true);
        @copy($path, $dir . '/' . basename($path) . '.backup-' . date('YmdHis'));
    }

    /** Moves backups created by earlier versions out of the application root. */
    public static function tidyOldBackups(string $path): void
    {
        $dir = storage_path('app/env-backups');
        foreach (glob($path . '.backup-*') ?: [] as $old) {
            if (!is_dir($dir)) @mkdir($dir, 0750, true);
            @rename($old, $dir . '/' . basename($old));
        }
    }

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
        // Password-reset links and HTTPS checks use APP_URL, so a localhost value must be corrected.
        if ($url = $this->option('url')) {
            $url = rtrim($url, '/');
            if (!preg_match('/^APP_URL=(.*)$/m', $contents, $current) || preg_match('~localhost|127\.0\.0\.1|^\s*$~', trim($current[1], " \"'"))) {
                $contents = preg_match('/^APP_URL=.*$/m', $contents) ? preg_replace('/^APP_URL=.*$/m', 'APP_URL=' . $url, $contents) : rtrim($contents, "\r\n") . "\nAPP_URL={$url}\n";
                $changed[] = 'APP_URL';
            }
        }
        foreach (self::DEFAULTS as $key => $value) {
            if (!preg_match('/^' . preg_quote($key, '/') . '=/m', $contents)) {
                $contents = rtrim($contents, "\r\n") . "\n{$key}={$value}\n"; $changed[] = $key;
            }
        }

        if ($contents !== $original) {
            self::backup($path);
            file_put_contents($path, $contents);
        }
        self::tidyOldBackups($path);
        $this->info($changed ? 'Updated .env: ' . implode(', ', $changed) : '.env already up to date.');
        return self::SUCCESS;
    }
}
