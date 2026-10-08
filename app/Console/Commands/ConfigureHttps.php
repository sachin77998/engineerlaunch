<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Switches the site to HTTPS only after confirming a valid certificate answers on the domain,
 * so enabling the redirect can never lock visitors out of an http-only site.
 */
class ConfigureHttps extends Command
{
    protected $signature = 'security:https {--enable : Force HTTPS if a valid certificate is active} {--disable : Serve over plain HTTP again} {--path= : .env file (default: application .env)} {--host= : Domain to check (default: APP_URL host)}';
    protected $description = 'Check the SSL certificate and enable or disable forced HTTPS';

    public function handle(): int
    {
        $path = $this->option('path') ?: base_path('.env');
        $host = $this->option('host') ?: (parse_url((string) config('app.url'), PHP_URL_HOST) ?: request()->getHost());
        if ($this->option('disable')) {
            $this->write($path, ['APP_FORCE_HTTPS' => 'false', 'APP_URL' => 'http://' . $host]);
            $this->info('HTTPS enforcement disabled.');
            return self::SUCCESS;
        }

        $this->line("Checking https://{$host} ...");
        try {
            $response = Http::timeout(15)->withOptions(['verify' => true, 'allow_redirects' => false])->get("https://{$host}/health");
            $valid = $response->status() > 0 && $response->status() < 500;
        } catch (Throwable $e) {
            $this->error('No valid SSL certificate on https://' . $host . ' yet (' . class_basename($e) . ').');
            $this->line('In cPanel open "SSL/TLS Status", select the domain and click "Run AutoSSL" (a temporary *.mytemp.website domain may need your real domain connected first). Then run this step again.');
            return self::FAILURE;
        }
        if (!$valid) {
            $this->error("https://{$host} answered with HTTP {$response->status()}; not enabling.");
            return self::FAILURE;
        }
        if (!$this->option('enable')) {
            $this->info("A valid certificate answers on https://{$host}. Run with --enable to force HTTPS.");
            return self::SUCCESS;
        }
        $this->write($path, ['APP_FORCE_HTTPS' => 'true', 'APP_URL' => 'https://' . $host]);
        Artisan::call('optimize:clear');
        $this->info("HTTPS is now enforced: visitors are redirected to https://{$host}, with HSTS and secure cookies.");
        return self::SUCCESS;
    }

    private function write(string $path, array $values): void
    {
        if (!is_file($path) || !is_writable($path)) { $this->warn("{$path} is not writable; set these manually: " . json_encode($values)); return; }
        $contents = file_get_contents($path);
        copy($path, $path . '.backup-' . date('YmdHis'));
        foreach ($values as $key => $value) {
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';
            $contents = preg_match($pattern, $contents) ? preg_replace($pattern, "{$key}={$value}", $contents) : rtrim($contents, "\r\n") . "\n{$key}={$value}\n";
        }
        file_put_contents($path, $contents);
    }
}
