<?php

namespace App\Services\Funds;

use Carbon\Carbon;

/**
 * Turns AMC monthly portfolio sheets into holdings. Works on the SEBI disclosure format shared by AMCs:
 * a header row naming the instrument, ISIN, rating/industry, quantity, market value and % to NAV (sometimes maturity
 * and YTM), repeated per section; the scheme name sits in the rows above the first header.
 */
class PortfolioParser
{
    private const HEADERS = [
        'name' => '/name of (the )?instrument|^instrument|name of (the )?security|^security name|^company name|^issuer/i',
        'isin' => '/\bisin/i',
        'rating' => '/rating|industry/i',
        'qty' => '/quantity|^qty|no\.? of (units|shares)/i',
        'mv' => '/market ?val|mkt ?val|market value|fair value|value \(rs/i',
        'pct' => '/%\s*(to|of)\s*(nav|net ?assets|aum)|% ?nav|percent(age)? to nav/i',
        'maturity' => '/maturity/i',
        'ytm' => '/ytm|yield/i',
    ];
    private const CASH = '/treps|tri-?party|reverse repo|\brepo\b|cblo|net (current )?(receivables?|payables?|assets)|cash (and|&) (cash )?equivalent|^cash\b|margin money|cdmdf|corporate debt market development/i';

    /** @return array<int, array{scheme: string, aliases: array, holdings: array}> */
    public function parse(array $sheets, ?Carbon $asOf = null): array
    {
        $out = [];
        foreach ($sheets as $sheetName => $rows) {
            $scheme = null; $cols = null; $holdings = [];
            foreach ($rows as $i => $row) {
                if ($this->isHeader($row)) {
                    $cols = $this->columns($row);
                    $scheme ??= $this->schemeName($rows, $i, (string) $sheetName);
                    continue;
                }
                if (!$cols || $scheme === null) continue;
                $h = $this->holding($row, $cols, $asOf);
                if ($h) $holdings[] = $h;
            }
            if ($scheme === null || !$holdings) continue;
            // Some AMCs give % to NAV as a fraction (0.0148 = 1.48%).
            $sum = array_sum(array_column($holdings, 'pct_nav'));
            if ($sum > 0.5 && $sum < 1.6) foreach ($holdings as &$h) $h['pct_nav'] = round($h['pct_nav'] * 100, 4);
            unset($h);
            [$name, $aliases] = $this->cleanName($scheme);
            $out[] = ['scheme' => $name, 'aliases' => $aliases, 'holdings' => $holdings];
        }
        return $out;
    }

    /** Lower-case key used to match a portfolio scheme to the scheme master (plan / option words removed). */
    public static function key(string $name): string
    {
        $n = strtolower(preg_replace('/\([^)]*\)/', ' ', $name));
        $n = str_replace(['&'], [' and '], $n);
        $n = preg_replace('/\b(direct|regular|plan|growth|idcw|option|dividend|payout|reinvestment|segregated portfolio ?\d*|the)\b/', ' ', $n);
        $n = preg_replace('/[^a-z0-9]+/', ' ', $n);
        return trim(preg_replace('/\s+/', ' ', $n));
    }

    private function isHeader(array $row): bool
    {
        $text = preg_replace('/\s+/', ' ', implode(' | ', $row));
        return preg_match(self::HEADERS['isin'], $text) && preg_match(self::HEADERS['pct'], $text) && preg_match('/instrument|security|issuer|company/i', $text);
    }

    private function columns(array $row): array
    {
        $cols = [];
        foreach ($row as $i => $cell) {
            $cell = trim(preg_replace('/\s+/', ' ', $cell));
            if ($cell === '') continue;
            foreach (self::HEADERS as $k => $re) {
                if (isset($cols[$k]) || !preg_match($re, $cell)) continue;
                if ($k === 'rating' && preg_match(self::HEADERS['name'], $cell)) continue;
                if ($k === 'ytm' && preg_match('/maturity/i', $cell) && !preg_match('/ytm|yield/i', $cell)) continue;
                $cols[$k] = $i;
                break;
            }
        }
        return isset($cols['name'], $cols['pct']) ? $cols : [];
    }

    private function schemeName(array $rows, int $headerRow, string $sheet): ?string
    {
        $skip = '/registration|registered|toll free|\bcin\b|asset management|trustee|suitable|investors should|risk-?o-?meter|benchmark|potential risk|^index$|^portfolio as on|e-?mail|website|^\(an? /i';
        for ($i = max(0, $headerRow - 25); $i < $headerRow; $i++) {
            foreach ($rows[$i] ?? [] as $j => $cell) {
                $c = trim(preg_replace('/\s+/', ' ', $cell));
                // "Scheme Name :" label with the name in the next cell.
                if (preg_match('/^scheme name\s*:?$/i', $c)) foreach (array_slice($rows[$i], $j + 1) as $next) if (trim($next) !== '') return trim($next);
                if (mb_strlen($c) < 6 || mb_strlen($c) > 220 || preg_match($skip, $c)) continue;
                if (preg_match('/^(.*?\bmutual fund)\s*$/i', $c)) continue;
                if (preg_match('/\b(fund|plan|etf|fof|scheme)\b/i', $c)) return $c;
            }
        }
        return preg_match('/fund|etf/i', $sheet) ? $sheet : null;
    }

    /** Scheme display name plus former names ("(Ex DSP Liquidity Fund)"). */
    private function cleanName(string $raw): array
    {
        $aliases = [];
        if (preg_match_all('/\((?:formerly(?: known as)?|ex[.:]?|erstwhile)\s*:?\s*([^)]+)\)/i', $raw, $m)) $aliases = array_map('trim', $m[1]);
        $n = preg_replace('/^\s*scheme name\s*:?\s*/i', '', $raw);
        $n = preg_replace('/^.*?portfolio (statement )?(of|for)\s+/i', '', $n);
        // Leading scheme codes: "IB02-Groww Liquid Fund", "WCGE - The Wealth Company Gold ETF".
        $n = preg_replace('/^[A-Z]{1,5}\d{0,4}\s*[-–:]\s*(?=[A-Z])/', '', $n);
        $n = preg_replace('/\s+(as on|as at|for the month|portfolio as).*$/i', '', $n);
        $n = trim(preg_replace('/\s*\(.*$/', '', $n));
        $n = ucwords(strtolower($n));
        $n = preg_replace_callback('/\b(Dsp|Tata|Lic|Ppfas|Bse|Nse|Crisil|Ibx|Psu|Etf|Fof|Hdfc|Sbi|Icici|Uti|Elss|Us|Aaa|Sdl|Gilt)\b/', fn ($x) => strtoupper($x[0]), $n);
        return [$n, $aliases];
    }

    private function holding(array $row, array $cols, ?Carbon $asOf): ?array
    {
        $get = fn ($k) => isset($cols[$k]) ? trim((string) ($row[$cols[$k]] ?? '')) : '';
        $name = preg_replace('/\s+/', ' ', trim(str_replace('**', '', $get('name'))));
        $pct = $this->number($get('pct'));
        if ($name === '' || $pct === null || abs($pct) < 1e-9) return null;
        if (preg_match('/^(sub[- ]?)?total|grand total|^net assets|^total net assets|^nil$/i', $name)) return null;
        $isin = strtoupper(preg_replace('/\s+/', '', $get('isin')));
        $isin = preg_match('/^IN[A-Z0-9]{10}$/', $isin) ? $isin : null;
        $isCash = (bool) preg_match(self::CASH, $name);
        if (!$isin && !$isCash) return null;
        $raw = $get('rating');
        [$rating, $type] = $this->classify($raw, $isin, $name, $isCash);
        $maturity = $this->maturity($get('maturity'), $name, $type, $asOf);
        return [
            'instrument' => mb_substr($name, 0, 300), 'isin' => $isin, 'issuer_key' => $this->issuerKey($isin, $name),
            'rating_raw' => $raw !== '' ? mb_substr($raw, 0, 120) : null, 'rating' => $rating, 'asset_type' => $type,
            'quantity' => $this->number($get('qty')), 'market_value_lakh' => $this->number($get('mv')), 'pct_nav' => round($pct, 6),
            'maturity_on' => $maturity?->toDateString(), 'ytm' => ($y = $this->number($get('ytm'))) !== null && $y > 0 && $y < 40 ? ($y < 0.4 ? $y * 100 : $y) : null,
        ];
    }

    /** Lowest rating named in the cell (agencies listed with "/" or "&"), and the asset type. */
    private function classify(string $raw, ?string $isin, string $name, bool $isCash): array
    {
        if ($isCash) return [null, 'cash'];
        // "[ICRA]AAA", "CRISIL-AAA", "CARE AA-(CE)": agency separators become spaces; a trailing minus stays a minus.
        $r = strtoupper(str_replace(['[', ']'], ' ', $raw));
        $r = preg_replace('/([A-Z])-(?=[A-Z])/', '$1 ', $r);
        if (preg_match('/\bSOV(EREIGN)?\b|\bGOI\b/', $r) || ($isin && preg_match('/^IN[0-9]/', $isin)) || preg_match('/\b(GOI|T[- ]?BILL|TREASURY BILL|SDL|STATE DEVELOPMENT|SGS|\d{2,3} ?D(AY)? ?TB)\b/i', $name)) return ['SOV', 'sovereign'];
        $short = [];
        if (preg_match_all('/(?<![A-Z0-9])A([1-4])(\+?)(?![0-9])/', $r, $m, PREG_SET_ORDER)) foreach ($m as $x) $short[] = 'A' . $x[1] . $x[2];
        $long = [];
        if (preg_match_all('/(?<![A-Z0-9])(AAA|AA|A|BBB|BB|B|C|D)([+-]?)(?![A-Z0-9+])/', $r, $m, PREG_SET_ORDER)) foreach ($m as $x) $long[] = $x[1] . $x[2];
        $scale = ['AAA', 'AA+', 'AA', 'AA-', 'A+', 'A', 'A-', 'BBB+', 'BBB', 'BBB-', 'BB+', 'BB', 'BB-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D'];
        $sScale = ['A1+', 'A1', 'A2+', 'A2', 'A3+', 'A3', 'A4+', 'A4', 'D'];
        $long = array_values(array_filter($long, fn ($x) => in_array($x, $scale, true)));
        if ($long) {
            usort($long, fn ($a, $b) => array_search($b, $scale, true) <=> array_search($a, $scale, true));
            return [$long[0], 'long'];
        }
        if ($short) {
            usort($short, fn ($a, $b) => array_search($b, $sScale, true) <=> array_search($a, $sScale, true));
            return [$short[0], 'short'];
        }
        if ($isin && preg_match('/^INF/', $isin)) return [null, 'other'];   // mutual fund / AIF units
        // Equity shares carry an industry in this column; debentures, CPs and CDs without a rating are unrated debt.
        // ISIN security code (characters 8-9): 07/08 debentures, 14 commercial paper, 16 certificates of deposit …
        if ($isin && preg_match('/^IN[E9]/', $isin) && preg_match('/^.{7}(07|08|14|16|17|19|20)/', $isin)) return [null, 'unrated'];
        if (preg_match('/debenture|\bncd\b|bond|commercial paper|certificate of deposit|\bcp\b|\bcd\b|ptc|pass through/i', $name)) return [null, 'unrated'];
        return [null, preg_match('/reit|invit/i', $name) ? 'other' : 'equity'];
    }

    private function maturity(string $cell, string $name, string $type, ?Carbon $asOf): ?Carbon
    {
        $asOf ??= now();
        if (in_array($type, ['cash'], true)) return $asOf->copy()->addDay();
        if ($cell !== '' && is_numeric($cell) && (float) $cell > 30000 && (float) $cell < 80000) return Carbon::create(1899, 12, 30)->addDays((int) $cell);
        if ($cell !== '' && ($d = $this->date($cell))) return $d;
        if ($d = $this->date($name)) return $d;
        if (preg_match('/\b(\d{2})(\d{2})(20\d{2})\b/', $name, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1]);
        if (preg_match('/\b(\d{2,3}) ?D(?:AY)?S? ?(?:TB|T-?BILL)/i', $name, $m)) return $asOf->copy()->addDays((int) min($m[1], 364) / 2);
        if (preg_match('/commercial paper|\bcp\b|certificate of deposit|\bcd\b|t-?bill|treasury bill/i', $name) || $type === 'short') return $asOf->copy()->addMonths(3);
        if (preg_match('/\b(20[2-9]\d)\b/', $name, $m) && (int) $m[1] >= $asOf->year) return Carbon::create((int) $m[1], 6, 30);
        return null;
    }

    private function date(string $s): ?Carbon
    {
        $months = 'jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec';
        if (preg_match('/\b(\d{1,2})[\/\-.](\d{1,2})[\/\-.](20\d{2})\b/', $s, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1]);
        if (preg_match('/\b(\d{1,2})[\s\-]?(' . $months . ')[a-z]*[\s\-,]*(\d{2,4})\b/i', $s, $m)) {
            $y = (int) $m[3]; $y = $y < 100 ? 2000 + $y : $y;
            if ($y >= 2000 && $y < 2100) return Carbon::parse($m[1] . ' ' . substr($m[2], 0, 3) . ' ' . $y);
        }
        if (preg_match('/\b(20\d{2})-(\d{2})-(\d{2})\b/', $s, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) return Carbon::create((int) $m[1], (int) $m[2], (int) $m[3]);
        return null;
    }

    /** Issuer = ISIN company code (characters 3-7); falls back to the leading words of the name. */
    private function issuerKey(?string $isin, string $name): string
    {
        if ($isin && preg_match('/^INE/', $isin)) return 'INE' . substr($isin, 3, 4);
        if ($isin && preg_match('/^IN[0-9]/', $isin)) return 'SOV';
        $n = strtolower(preg_replace('/[^a-z ]/i', ' ', preg_replace('/\d+(\.\d+)?\s*%/', ' ', $name)));
        return mb_substr(trim(preg_replace('/\s+/', ' ', implode(' ', array_slice(preg_split('/\s+/', trim($n)), 0, 3)))), 0, 80);
    }

    private function number(string $v): ?float
    {
        $v = str_replace([',', '%', ' '], '', $v);
        // Accounting negatives: "(11.20)" is -11.20 (net payables).
        if (preg_match('/^\((.+)\)$/', $v, $m)) $v = '-' . $m[1];
        if ($v === '' || !is_numeric($v)) return null;
        return (float) $v;
    }
}
