<?php

namespace App\Services\Trading;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Stock and ETF master: NSE equity list + NSE ETF list (with underlying), Kite instrument tokens (for live
 * WebSocket ticks) for NSE and BSE, BSE-only companies from the Kite master, end-of-day OHLC and volume from the
 * NSE bhavcopy, applicable margin %, and the link to company pages.
 */
class InstrumentSync
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';

    public function sync(): array
    {
        $now = now();
        $nse = $this->csv('https://nsearchives.nseindia.com/content/equities/EQUITY_L.csv');
        $etfs = $this->csv('https://nsearchives.nseindia.com/content/equities/eq_etfseclist.csv');
        $kite = $this->kiteMaster();
        $rows = [];
        foreach ($nse as $r) {
            $symbol = $r['SYMBOL'] ?? null;
            if (!$symbol) continue;
            $rows['NSE|' . $symbol] = ['exchange' => 'NSE', 'symbol' => $symbol, 'name' => $r['NAME OF COMPANY'] ?? null, 'instrument_type' => 'STOCK', 'series' => $r['SERIES'] ?? null,
                'isin' => $r['ISIN NUMBER'] ?? null, 'lot_size' => (int) ($r['MARKET LOT'] ?? 1) ?: 1, 'face_value' => is_numeric($r['FACE VALUE'] ?? null) ? (float) $r['FACE VALUE'] : null,
                'listed_on' => $this->date($r['DATE OF LISTING'] ?? null), 'underlying' => null, 'etf_class' => null];
        }
        foreach ($etfs as $r) {
            $symbol = $r['Symbol'] ?? null;
            if (!$symbol) continue;
            $rows['NSE|' . $symbol] = ['exchange' => 'NSE', 'symbol' => $symbol, 'name' => $r['Underlying Asset'] ?? $r['SecurityName'] ?? null, 'instrument_type' => 'ETF', 'series' => 'EQ',
                'isin' => $r['ISINNumber'] ?? null, 'lot_size' => (int) ($r['MarketLot'] ?? 1) ?: 1, 'face_value' => is_numeric($r['FaceValue'] ?? null) ? (float) $r['FaceValue'] : null,
                'listed_on' => $this->date($r['DateofListing'] ?? null), 'underlying' => $r['Underlying Key'] ?? $r['Underlying Asset'] ?? null, 'etf_class' => strtoupper((string) ($r['ETF Underlying'] ?? '')) ?: null];
        }
        // Kite tokens for NSE symbols; BSE companies that are not on NSE.
        $nseNames = collect($rows)->map(fn ($r) => $this->nameKey((string) $r['name']))
            ->merge(collect($kite)->where('exchange', 'NSE')->map(fn ($k) => $this->nameKey($k['name'])))->flip();
        $nseSymbols = collect($rows)->pluck('symbol')->flip();
        foreach ($kite as $k) {
            if ($k['exchange'] === 'NSE' && isset($rows['NSE|' . $k['tradingsymbol']])) {
                $key = 'NSE|' . $k['tradingsymbol'];
                $rows[$key]['kite_token'] = (int) $k['instrument_token'];
                $rows[$key]['tick_size'] = (float) $k['tick_size'];
                // ETFs: the fund's full name ("Nippon India ETF Nifty 50 BeES"); the index stays in underlying.
                if ($rows[$key]['instrument_type'] === 'ETF' && $k['name'] !== '') $rows[$key]['name'] = str_replace(['Etf', 'Bees', 'Nifty', 'Psu', 'It ', 'Sbi', 'Hdfc', 'Icici', 'Uti'], ['ETF', 'BeES', 'Nifty', 'PSU', 'IT ', 'SBI', 'HDFC', 'ICICI', 'UTI'], ucwords(strtolower($k['name'])));
            } elseif ($k['exchange'] === 'BSE' && $k['name'] !== '' && !isset($nseNames[$this->nameKey($k['name'])]) && !isset($nseSymbols[$k['tradingsymbol']]) && preg_match('/^[A-Z0-9&]+$/', $k['tradingsymbol'])) {
                $rows['BSE|' . $k['tradingsymbol']] = ['exchange' => 'BSE', 'symbol' => $k['tradingsymbol'], 'name' => $k['name'],
                    'instrument_type' => preg_match('/(ETF|BEES)/i', $k['name']) ? 'ETF' : 'STOCK', 'series' => null, 'isin' => null,
                    'lot_size' => (int) $k['lot_size'] ?: 1, 'face_value' => null, 'listed_on' => null, 'underlying' => null, 'etf_class' => null,
                    'kite_token' => (int) $k['instrument_token'], 'tick_size' => (float) $k['tick_size']];
            }
        }
        $payload = array_map(fn ($r) => $r + ['kite_token' => null, 'tick_size' => 0.05, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now], array_values($rows));
        foreach (array_chunk($payload, 500) as $chunk) {
            DB::table('market_instruments')->upsert($chunk, ['exchange', 'symbol'], ['name', 'instrument_type', 'series', 'isin', 'lot_size', 'face_value', 'listed_on', 'underlying', 'etf_class', 'kite_token', 'tick_size', 'is_active', 'updated_at']);
        }
        // Listings that dropped out of the sources (delisted, or now matched to NSE) are hidden.
        DB::table('market_instruments')->where('updated_at', '<', $now)->update(['is_active' => false]);
        $prices = $this->prices();
        DB::statement("UPDATE market_instruments i JOIN margin_rates m ON m.symbol = i.symbol AND m.series = 'EQ' SET i.margin_pct = m.applicable_margin WHERE i.exchange = 'NSE'");
        DB::statement('UPDATE market_instruments i JOIN listed_companies l ON l.symbol = i.symbol SET i.company_id = l.company_id WHERE i.exchange = \'NSE\' AND l.company_id IS NOT NULL');
        Cache::forget('trading:instrument-facets');
        return ['instruments' => count($payload), 'nse_stocks' => count($nse), 'etfs' => count($etfs), 'bse_only' => collect($rows)->where('exchange', 'BSE')->count(), 'priced' => $prices];
    }

    /** End-of-day prices from the latest NSE bhavcopy. */
    public function prices(): int
    {
        for ($day = Carbon::now('Asia/Kolkata'), $tries = 0; $tries < 8; $day = $day->subDay(), $tries++) {
            if ($day->isWeekend()) continue;
            try {
                $text = $this->get('https://nsearchives.nseindia.com/products/content/sec_bhavdata_full_' . $day->format('dmY') . '.csv');
            } catch (Throwable) {
                continue;
            }
            $count = 0;
            $lines = preg_split('/\r?\n/', trim($text));
            array_shift($lines);
            foreach (array_chunk($lines, 400) as $chunk) {
                DB::transaction(function () use ($chunk, $day, &$count) {
                    foreach ($chunk as $line) {
                        $c = array_map('trim', explode(',', $line));
                        if (count($c) < 12 || !in_array($c[1], ['EQ', 'BE', 'BZ', 'SM', 'ST'], true) || !is_numeric($c[8])) continue;
                        $prev = (float) $c[3];
                        $count += DB::table('market_instruments')->where('exchange', 'NSE')->where('symbol', $c[0])->update([
                            'prev_close' => $prev, 'open' => (float) $c[4], 'high' => (float) $c[5], 'low' => (float) $c[6], 'last_price' => (float) $c[8],
                            'change_pct' => $prev > 0 ? round(((float) $c[8] - $prev) / $prev * 100, 2) : null, 'volume' => (int) $c[10], 'turnover_lakhs' => (float) $c[11],
                            'price_date' => $day->toDateString(),
                        ]);
                    }
                });
            }
            if ($count) return $count;
        }
        return 0;
    }

    /** Kite instrument master, NSE and BSE cash equities only (public CSV). */
    private function kiteMaster(): array
    {
        try {
            $text = $this->get('https://api.kite.trade/instruments');
        } catch (Throwable $e) {
            report($e);
            return [];
        }
        $out = [];
        $lines = preg_split('/\r?\n/', $text);
        $head = str_getcsv(array_shift($lines));
        foreach ($lines as $line) {
            if ($line === '' || !preg_match('/,(NSE|BSE)$/', $line)) continue;
            $r = array_combine($head, array_pad(str_getcsv($line), count($head), ''));
            if ($r['instrument_type'] !== 'EQ' || !in_array($r['segment'], ['NSE', 'BSE'], true)) continue;
            $out[] = $r;
        }
        return $out;
    }

    private function csv(string $url): array
    {
        $lines = preg_split('/\r?\n/', trim($this->get($url)));
        $head = array_map('trim', str_getcsv(array_shift($lines)));
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $values = array_map('trim', str_getcsv($line));
            if (count($values) < count($head)) $values = array_pad($values, count($head), '');
            $rows[] = array_combine($head, array_slice($values, 0, count($head)));
        }
        return $rows;
    }

    private function get(string $url): string
    {
        $r = Http::withHeaders(['User-Agent' => self::UA, 'Accept' => '*/*', 'Referer' => 'https://www.nseindia.com/'])->timeout(120)->retry(2, 3000, throw: false)->get($url);
        if (!$r->successful()) throw new RuntimeException('HTTP ' . $r->status() . ' for ' . $url);
        return $r->body();
    }

    private function date(?string $v): ?string
    {
        if (!$v) return null;
        foreach (['d-M-Y', 'd-M-y'] as $f) {
            try { return Carbon::createFromFormat($f, $v)->toDateString(); } catch (Throwable) {}
        }
        return null;
    }

    private function nameKey(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(preg_replace('/\b(limited|ltd|the|india)\b/i', '', $name)));
    }
}
