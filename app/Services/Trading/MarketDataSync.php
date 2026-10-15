<?php

namespace App\Services\Trading;

use App\Models\Trading\MarginRate;
use App\Models\Trading\TradingHoliday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Public market data: every mutual fund scheme and its NAV (AMFI), 1/3/5-year returns (AMFI NAV history),
 * exchange holidays (NSE) and applicable margin rates per security (NSE clearing VaR file).
 */
class MarketDataSync
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36';
    private const NAV_ALL = 'https://portal.amfiindia.com/spages/NAVAll.txt';
    private const NAV_HISTORY = 'https://portal.amfiindia.com/DownloadNAVHistoryReport_Po.aspx';

    /** Imports today's NAV for every scheme. Returns [schemes, new NAVs]. */
    public function syncNavs(): array
    {
        $rows = $this->parseNavFile($this->download(self::NAV_ALL));
        if (count($rows) < 1000) throw new RuntimeException('AMFI NAV file looked incomplete (' . count($rows) . ' schemes).');
        $existing = DB::table('mf_schemes')->pluck('nav_date', 'scheme_code')->all();
        $navs = DB::table('mf_schemes')->pluck('nav', 'scheme_code')->all();
        $now = now();
        $schemes = $history = [];
        foreach ($rows as $r) {
            $changed = isset($existing[$r['scheme_code']]) && $existing[$r['scheme_code']] !== $r['nav_date'];
            $schemes[] = $r + ['prev_nav' => $changed ? $navs[$r['scheme_code']] : null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now];
            $history[] = ['scheme_code' => $r['scheme_code'], 'nav_date' => $r['nav_date'], 'nav' => $r['nav']];
        }
        foreach (array_chunk($schemes, 500) as $chunk) {
            // prev_nav only moves when the NAV date moves, so re-running the same day keeps the day change.
            DB::table('mf_schemes')->upsert($chunk, ['scheme_code'], ['isin_growth', 'isin_reinvest', 'name', 'amc', 'scheme_type', 'category', 'asset_class', 'plan', 'option', 'nav', 'nav_date', 'is_active', 'updated_at']);
            foreach ($chunk as $s) {
                if ($s['prev_nav'] !== null) DB::table('mf_schemes')->where('scheme_code', $s['scheme_code'])->update(['prev_nav' => $s['prev_nav']]);
            }
        }
        foreach (array_chunk($history, 1000) as $chunk) DB::table('mf_nav_history')->insertOrIgnore($chunk);
        // Schemes missing from the file for two weeks are no longer offered.
        DB::table('mf_schemes')->where('nav_date', '<', now()->subDays(14)->toDateString())->update(['is_active' => false]);
        DB::table('mf_nav_history')->where('nav_date', '<', now()->subDays(400)->toDateString())->delete();
        Cache::forget('trading:fund-facets');
        return [count($schemes), count($history)];
    }

    /** 1, 3 and 5-year returns (CAGR for 3 and 5 years) from AMFI's NAV history on those dates. */
    public function syncReturns(): int
    {
        $current = DB::table('mf_schemes')->where('is_active', true)->get(['scheme_code', 'nav', 'nav_date'])->keyBy('scheme_code');
        $updates = [];
        foreach ([0 => 'return_6m', 1 => 'return_1y', 3 => 'return_3y', 5 => 'return_5y'] as $years => $column) {
            [$date, $past] = $this->navsOn($years === 0 ? Carbon::now()->subMonths(6) : Carbon::now()->subYears($years));
            if (!$past) continue;
            foreach ($past as $code => $oldNav) {
                $s = $current[$code] ?? null;
                if (!$s || $oldNav <= 0 || !$s->nav) continue;
                $span = max(0.5, Carbon::parse($date)->diffInDays(Carbon::parse($s->nav_date)) / 365.25);
                $ratio = $s->nav / $oldNav;
                $updates[$code][$column] = round($years <= 1 ? ($ratio - 1) * 100 : (pow($ratio, 1 / $span) - 1) * 100, 2);
            }
        }
        foreach (array_chunk($updates, 500, true) as $chunk) {
            DB::transaction(function () use ($chunk) {
                foreach ($chunk as $code => $values) DB::table('mf_schemes')->where('scheme_code', $code)->update($values);
            });
        }
        return count($updates);
    }

    /** Exchange holidays for the capital market segment (current list from NSE). */
    public function syncHolidays(): int
    {
        $response = Http::withHeaders(['User-Agent' => self::UA, 'Referer' => 'https://www.nseindia.com/', 'Accept' => 'application/json'])
            ->timeout(60)->retry(2, 3000, throw: false)->get('https://www.nseindia.com/api/holiday-master', ['type' => 'trading']);
        $json = $response->json();
        if (!is_array($json)) throw new RuntimeException('NSE holiday list unavailable (HTTP ' . $response->status() . ').');
        $count = 0;
        foreach ($json['CM'] ?? $json['CBM'] ?? [] as $h) {
            try { $day = Carbon::createFromFormat('d-M-Y', $h['tradingDate'])->toDateString(); } catch (Throwable) { continue; }
            TradingHoliday::updateOrCreate(['holiday_on' => $day, 'segment' => 'CM'], ['description' => trim((string) ($h['description'] ?? 'Holiday')), 'source' => 'NSE']);
            $count++;
        }
        Cache::forget('trading:holidays');
        return $count;
    }

    /** Applicable margin rates (VaR + ELM) for every security from NSE's latest VaR file. */
    public function syncMargins(): int
    {
        for ($day = Carbon::now('Asia/Kolkata'), $tries = 0; $tries < 7; $day = $day->subDay(), $tries++) {
            if ($day->isWeekend()) continue;
            foreach ([6, 5, 4, 3, 2, 1] as $n) {
                try {
                    $text = $this->download('https://nsearchives.nseindia.com/archives/nsccl/var/C_VAR1_' . $day->format('dmY') . '_' . $n . '.DAT');
                } catch (Throwable) {
                    continue;
                }
                $rows = [];
                $now = now();
                foreach (preg_split('/\r?\n/', $text) as $line) {
                    $c = explode(',', trim($line));
                    if (($c[0] ?? '') !== '20' || count($c) < 10 || !is_numeric($c[9])) continue;
                    $rows[$c[1] . '|' . $c[2]] = ['symbol' => $c[1], 'series' => $c[2], 'var_margin' => (float) $c[6], 'elm' => (float) $c[7], 'applicable_margin' => (float) $c[9],
                        'as_of' => $day->toDateString(), 'created_at' => $now, 'updated_at' => $now];
                }
                if (!$rows) continue;
                foreach (array_chunk(array_values($rows), 1000) as $chunk) {
                    MarginRate::upsert($chunk, ['symbol', 'series'], ['var_margin', 'elm', 'applicable_margin', 'as_of', 'updated_at']);
                }
                return count($rows);
            }
        }
        return 0;
    }

    /** Parses AMFI NAV files (today's file or a history report), using the header to find columns. */
    public function parseNavFile(string $text): array
    {
        $lines = preg_split('/\r?\n/', $text);
        $header = array_map(fn ($h) => strtolower(trim($h)), explode(';', (string) array_shift($lines)));
        $col = function (string ...$names) use ($header) {
            foreach ($header as $i => $h) foreach ($names as $n) if (str_contains($h, $n)) return $i;
            return null;
        };
        $iCode = $col('scheme code'); $iName = $col('scheme name', 'nav name'); $iPlan = $col('plan'); $iOption = $col('option');
        $iIsin = $col('isin growth', 'isin div payout'); $iReinvest = $col('reinvestment'); $iNav = $col('net asset value'); $iDate = $col('date');
        $rows = [];
        $type = $category = $amc = null;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (!str_contains($line, ';')) {
                if (preg_match('/^(Open Ended|Close Ended|Interval Fund)[^(]*\((.+)\)\s*$/i', $line, $m)) {
                    $type = ucwords(strtolower(trim(preg_replace('/\s*Schemes?$/i', '', $m[1]))));
                    // "Equity Schemes - X" and "Equity Scheme - X" are the same category.
                    $category = preg_replace(['/^(\w+) Schemes\b/', '/\s+/'], ['$1 Scheme', ' '], trim($m[2]));
                } else {
                    $amc = $line;
                }
                continue;
            }
            $c = array_map('trim', explode(';', $line));
            $nav = $c[$iNav] ?? null;
            if (!is_numeric($c[$iCode] ?? null) || !is_numeric($nav)) continue;
            try { $date = Carbon::createFromFormat('d-M-Y', $c[$iDate])->toDateString(); } catch (Throwable) { continue; }
            $name = $c[$iName];
            $isin = fn ($i) => $i !== null && preg_match('/^IN[A-Z0-9]{10}$/', $c[$i] ?? '') ? $c[$i] : null;
            $rows[] = [
                'scheme_code' => (int) $c[$iCode],
                'isin_growth' => $isin($iIsin),
                'isin_reinvest' => $isin($iReinvest),
                'name' => mb_substr($name, 0, 250),
                'amc' => mb_substr((string) $amc, 0, 120),
                'scheme_type' => $type,
                'category' => $category ? mb_substr($category, 0, 120) : null,
                'asset_class' => self::assetClass($category),
                'plan' => $this->plan($iPlan !== null ? ($c[$iPlan] ?? '') : '', $name),
                'option' => $this->option($iOption !== null ? ($c[$iOption] ?? '') : '', $name),
                'nav' => (float) $nav,
                'nav_date' => $date,
            ];
        }
        return $rows;
    }

    /** Broad asset class from an AMFI category name. */
    public static function assetClass(?string $category): ?string
    {
        if (!$category) return null;
        return match (true) {
            (bool) preg_match('/\bETFs?\b|Exchange Traded/i', $category) => 'ETF',
            (bool) preg_match('/Index Funds?/i', $category) => 'Index Fund',
            (bool) preg_match('/Fund of Funds|FoF/i', $category) => 'Fund of Funds',
            (bool) preg_match('/Solution|Child|Retirement/i', $category) => 'Solution Oriented',
            (bool) preg_match('/Hybrid|Balanced|Arbitrage|Asset Allocation|Equity Savings/i', $category) => 'Hybrid',
            (bool) preg_match('/^(Equity|Growth|ELSS)|Tax Sav/i', $category) => 'Equity',
            (bool) preg_match('/Debt|Income|Gilt|Liquid|Money Market|Overnight|Duration|Bond|Credit|Floater|Banking and PSU/i', $category) => 'Debt',
            default => 'Other',
        };
    }

    /** NAVs of all schemes on the nearest business day on or before a date: [date, [code => nav]]. */
    private function navsOn(Carbon $date): array
    {
        for ($try = 0; $try < 6; $try++, $date = $date->copy()->subDay()) {
            if ($date->isWeekend()) continue;
            try {
                $rows = $this->parseNavFile($this->download(self::NAV_HISTORY . '?frmdt=' . $date->format('d-M-Y')));
            } catch (Throwable $e) {
                report($e);
                continue;
            }
            if (count($rows) > 1000) return [$date->toDateString(), array_column($rows, 'nav', 'scheme_code')];
        }
        return [null, []];
    }

    private function plan(string $value, string $name): ?string
    {
        $v = $value . ' ' . $name;
        if (preg_match('/\bdirect\b/i', $v)) return 'Direct';
        if (preg_match('/\b(regular|retail)\b/i', $v)) return 'Regular';
        return null;
    }

    private function option(string $value, string $name): ?string
    {
        $v = $value . ' ' . $name;
        if (preg_match('/\b(idcw|dividend|bonus|payout|reinvest)/i', $v)) return 'IDCW';
        if (preg_match('/\bgrowth\b/i', $v)) return 'Growth';
        return null;
    }

    private function download(string $url): string
    {
        // Current Mozilla CA bundle: AMFI's certificate chains to a root that older server bundles lack.
        $response = Http::withHeaders(['User-Agent' => self::UA, 'Accept' => '*/*'])->withOptions(['verify' => resource_path('certs/cacert.pem')])->timeout(180)->retry(2, 5000, throw: false)->get($url);
        if (!$response->successful()) throw new RuntimeException('HTTP ' . $response->status() . ' for ' . $url);
        return $response->body();
    }
}
