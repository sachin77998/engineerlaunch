<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

class VisitorLocation
{
    public function lookup(string $ip): array
    {
        if (!config('visitor_analytics.geolocation_enabled') ||
            !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return [];
        $key = 'visitor-geo:'.hash_hmac('sha256', $ip, config('app.key'));
        if (Cache::has($key)) return Cache::get($key, []);
        $budget = 'visitor-geo-budget:'.now('UTC')->toDateString();
        if (config('visitor_analytics.daily_lookup_limit') < 1 || RateLimiter::tooManyAttempts($budget, config('visitor_analytics.daily_lookup_limit'))) return [];
        RateLimiter::hit($budget, 86400);
        $location = [];
        try {
            $response = Http::acceptJson()->connectTimeout(1)->timeout(2)
                ->get('https://api.db-ip.com/v2/'.rawurlencode(config('visitor_analytics.dbip_key')).'/'.rawurlencode($ip));
            $data = $response->json();
            if ($response->successful() && is_array($data) && empty($data['error']) &&
                preg_match('/^[A-Z]{2}$/', $data['countryCode'] ?? '') && is_string($data['stateProv'] ?? null)) {
                $location = ['country_code'=>$data['countryCode'], 'state'=>mb_substr(trim($data['stateProv']), 0, 150)];
            }
        } catch (\Throwable $e) {
            // Geography is optional; an outage must not affect visits or page responses.
        }
        Cache::put($key, $location, $location ? 604800 : 3600);
        return $location;
    }
}
