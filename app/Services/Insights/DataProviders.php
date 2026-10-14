<?php

namespace App\Services\Insights;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Paid company-data APIs, used only when their keys are set in .env:
 *  - Surepass (SUREPASS_TOKEN): MCA company details by CIN, GSTIN details, DIN details.
 *  - InstaFinancials (INSTAFINANCIALS_API_KEY): CIN search, InstaBasic master data, InstaSummary financials.
 *  - RapidAPI (RAPIDAPI_KEY + host and URL templates of the chosen company-data API on RapidAPI).
 * Endpoint paths are configurable (config/services.php) because providers version their APIs.
 * Every call returns the provider's raw JSON; CompanyData normalises it.
 */
class DataProviders
{
    /** @return string[] providers with credentials, in the configured order */
    public function enabled(): array
    {
        $order = array_filter(array_map('trim', explode(',', (string) config('services.insights.providers', 'surepass,instafinancials,rapidapi'))));
        return array_values(array_filter($order, fn ($p) => match ($p) {
            'surepass' => filled(config('services.surepass.token')),
            'instafinancials' => filled(config('services.instafinancials.api_key')) && filled(config('services.instafinancials.base_url')),
            'rapidapi' => filled(config('services.rapidapi.key')) && filled(config('services.rapidapi.host')),
            default => false,
        }));
    }

    /** Raw responses for a CIN from every enabled provider: [provider => json]. */
    public function company(string $cin): array
    {
        $out = [];
        foreach ($this->enabled() as $provider) {
            try {
                $out[$provider] = match ($provider) {
                    'surepass' => $this->surepass('company', ['id_number' => $cin]),
                    'instafinancials' => $this->instaCompany($cin),
                    'rapidapi' => $this->rapid('company', ['cin' => $cin]),
                };
            } catch (Throwable $e) {
                $out[$provider] = ['_error' => $e->getMessage()];
            }
        }
        return array_filter($out, fn ($json) => $json !== null && $json !== []);
    }

    public function gstin(string $gstin): array
    {
        $out = [];
        foreach ($this->enabled() as $provider) {
            try {
                $json = match ($provider) {
                    'surepass' => $this->surepass('gstin', ['id_number' => $gstin, 'filing_status_get' => true]),
                    'rapidapi' => $this->rapid('gstin', ['gstin' => $gstin]),
                    default => null,
                };
                if ($json) $out[$provider] = $json;
            } catch (Throwable $e) {
                $out[$provider] = ['_error' => $e->getMessage()];
            }
        }
        return $out;
    }

    public function din(string $din): array
    {
        $out = [];
        foreach ($this->enabled() as $provider) {
            try {
                $json = match ($provider) {
                    'surepass' => $this->surepass('din', ['id_number' => $din]),
                    'rapidapi' => $this->rapid('din', ['din' => $din]),
                    default => null,
                };
                if ($json) $out[$provider] = $json;
            } catch (Throwable $e) {
                $out[$provider] = ['_error' => $e->getMessage()];
            }
        }
        return $out;
    }

    /** [{name, cin}] for a company name. */
    public function search(string $name): array
    {
        $rows = [];
        foreach ($this->enabled() as $provider) {
            try {
                $json = match ($provider) {
                    'instafinancials' => app(InstaFinancials::class)->searchCin($name),
                    'surepass' => config('services.surepass.paths.search') ? $this->surepass('search', ['company_name' => $name]) : null,
                    'rapidapi' => config('services.rapidapi.urls.search') ? $this->rapid('search', ['name' => $name]) : null,
                };
                if ($json) $rows = array_merge($rows, InstaFinancials::searchResults($json));
            } catch (Throwable $e) {
                report($e);
            }
        }
        return collect($rows)->unique('cin')->values()->all();
    }

    private function surepass(string $endpoint, array $body): ?array
    {
        $path = config("services.surepass.paths.{$endpoint}");
        if (!$path) return null;
        $response = Http::withToken(config('services.surepass.token'))->acceptJson()->timeout(60)
            ->post(rtrim(config('services.surepass.base_url'), '/') . '/' . ltrim($path, '/'), $body);
        $json = $response->json();
        if (!$response->successful() || !is_array($json) || (isset($json['success']) && $json['success'] === false)) {
            throw new RuntimeException('Surepass ' . $endpoint . ' failed (HTTP ' . $response->status() . '): ' . mb_substr((string) ($json['message'] ?? $response->body()), 0, 200));
        }
        return $json['data'] ?? $json;
    }

    private function instaCompany(string $cin): array
    {
        $insta = app(InstaFinancials::class);
        $out = ['basic' => $insta->basic($cin)];
        // Financial summary (InstaSummary) — the path is configurable in case InstaFinancials versions it.
        if ($path = config('services.instafinancials.summary_path')) {
            try { $out['summary'] = $insta->getPath(str_replace('{cin}', rawurlencode($cin), $path)); } catch (Throwable $e) { $out['summary_error'] = $e->getMessage(); }
        }
        return $out;
    }

    /** RapidAPI: GET a configured URL template, e.g. https://host/company/{cin}. */
    private function rapid(string $endpoint, array $values): ?array
    {
        $template = config("services.rapidapi.urls.{$endpoint}");
        if (!$template) return null;
        $url = preg_replace_callback('/\{(\w+)\}/', fn ($m) => rawurlencode((string) ($values[$m[1]] ?? '')), $template);
        $response = Http::withHeaders(['X-RapidAPI-Key' => config('services.rapidapi.key'), 'X-RapidAPI-Host' => config('services.rapidapi.host')])
            ->acceptJson()->timeout(60)->get($url);
        if (!$response->successful() || !is_array($response->json())) throw new RuntimeException('RapidAPI ' . $endpoint . ' failed (HTTP ' . $response->status() . ')');
        return $response->json();
    }
}
