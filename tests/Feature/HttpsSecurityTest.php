<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpsSecurityTest extends TestCase
{
    public function test_http_is_served_while_https_is_not_forced(): void
    {
        config(['app.force_https' => false]);
        $this->get('/health')->assertOk()->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_forced_https_redirects_and_sends_hsts_over_https(): void
    {
        config(['app.force_https' => true]);
        $this->get('http://localhost/health')->assertStatus(308)->assertRedirect('https://localhost/health');
        $this->get('https://localhost/health')->assertOk()->assertHeader('Strict-Transport-Security');
    }

    public function test_enable_command_refuses_without_a_valid_certificate(): void
    {
        $path = storage_path('framework/testing-https.env');
        file_put_contents($path, "APP_URL=http://portal.example\nAPP_FORCE_HTTPS=false\n");
        config(['app.url' => 'http://portal.example']);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('SSL certificate problem'));
        $this->artisan('security:https', ['--enable' => true, '--path' => $path])->assertFailed();
        $this->assertStringContainsString('APP_FORCE_HTTPS=false', file_get_contents($path));
        array_map('unlink', array_merge(glob($path . '*'), glob(storage_path('app/env-backups/' . basename($path) . '*'))));
    }

    public function test_enable_command_switches_to_https_when_certificate_answers(): void
    {
        $path = storage_path('framework/testing-https-ok.env');
        file_put_contents($path, "APP_URL=http://portal.example\nAPP_FORCE_HTTPS=false\n");
        config(['app.url' => 'http://portal.example']);
        Http::fake(['https://portal.example/health' => Http::response(['status' => 'ok'])]);
        $this->artisan('security:https', ['--enable' => true, '--path' => $path])->assertSuccessful();
        $this->assertStringContainsString('APP_FORCE_HTTPS=true', file_get_contents($path));
        $this->assertStringContainsString('APP_URL=https://portal.example', file_get_contents($path));
        array_map('unlink', array_merge(glob($path . '*'), glob(storage_path('app/env-backups/' . basename($path) . '*'))));
    }
}
