<?php

namespace App\Services\Market;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Market today: index levels (Yahoo Finance chart data, 15-minute cache) and NSE / US movers from the stored quotes. */
class MarketSnapshot
{
    public const INDIA = ['^BSESN' => 'SENSEX', '^NSEI' => 'Nifty 50', '^NSEBANK' => 'Nifty Bank', 'NIFTY_MIDCAP_100.NS' => 'NIFTY MIDCAP 100', '^CRSLDX' => 'Nifty 500'];
    public const US = ['^GSPC' => 'S&P 500', '^IXIC' => 'Nasdaq Composite', '^DJI' => 'Dow Jones'];

    /** @return array<int, array{name:string, price:float, change:float, change_pct:float, time:?int}> */
    public function indices(array $symbols): array
    {
        return \App\Support\Stale::remember('market-snapshot:indices:' . md5(implode(',', array_keys($symbols))), now()->addMinutes(15), function () use ($symbols) {
            $out = [];
            // All indices requested in parallel: one slow answer must not add up.
            try {
                $responses = Http::pool(fn ($pool) => array_map(fn ($symbol) => $pool->as($symbol)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36'])
                    ->timeout(6)->get('https://query1.finance.yahoo.com/v8/finance/chart/' . rawurlencode($symbol), ['range' => '1d', 'interval' => '1d']), array_keys($symbols)));
            } catch (Throwable) {
                return [];
            }
            foreach ($symbols as $symbol => $name) {
                $response = $responses[$symbol] ?? null;
                $meta = $response instanceof \Illuminate\Http\Client\Response && $response->successful() ? $response->json('chart.result.0.meta') : null;
                if (!$meta) continue;
                $price = $meta['regularMarketPrice'] ?? null;
                $prev = $meta['chartPreviousClose'] ?? $meta['previousClose'] ?? null;
                if (!$price || !$prev) continue;
                $out[] = ['name' => $name, 'price' => (float) $price, 'change' => $price - $prev, 'change_pct' => ($price / $prev - 1) * 100, 'time' => $meta['regularMarketTime'] ?? null];
            }
            return $out;
        });
    }

    /** NSE stocks: breadth, top gainers / losers (₹1,000 Cr+ turnover-ranked universe) and most active by value. */
    public function india(): array
    {
        return \App\Support\Stale::remember('market-snapshot:india:' . DB::table('market_instruments')->max('updated_at'), now()->addMinutes(30), function () {
            $base = fn () => DB::table('market_instruments')->where('exchange', 'NSE')->where('instrument_type', 'STOCK')->where('is_active', true)->whereNotNull('change_pct');
            return [
                'up' => $base()->where('change_pct', '>', 0)->count(), 'down' => $base()->where('change_pct', '<', 0)->count(),
                'gainers' => $base()->where('turnover_lakhs', '>', 500)->orderByDesc('change_pct')->limit(5)->get(['symbol', 'name', 'last_price', 'change_pct']),
                'losers' => $base()->where('turnover_lakhs', '>', 500)->orderBy('change_pct')->limit(5)->get(['symbol', 'name', 'last_price', 'change_pct']),
                'active' => $base()->whereNotNull('turnover_lakhs')->orderByDesc('turnover_lakhs')->limit(5)->get(['symbol', 'name', 'last_price', 'change_pct', 'turnover_lakhs']),
                'date' => $base()->max('price_date'),
            ];
        });
    }
}
