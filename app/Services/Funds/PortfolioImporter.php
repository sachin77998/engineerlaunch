<?php

namespace App\Services\Funds;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

/**
 * Fetches each AMC's latest monthly portfolio disclosure (mf_portfolio_sources), parses the workbooks into
 * mf_portfolio_holdings and scores every debt scheme into mf_credit_quality. Already-parsed files are skipped.
 */
class PortfolioImporter
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124 Safari/537.36';
    private const MONTHS = ['jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12];

    public function __construct(private XlsxReader $reader, private PortfolioParser $parser, private FundCreditQuality $quality) {}

    /** Adds configured sources missing from the table (owner edits to existing rows are kept). */
    public function seedSources(): int
    {
        $n = 0;
        foreach (config('fund_portfolios.sources') as $amc => [$url, $pattern]) {
            if (DB::table('mf_portfolio_sources')->where('amc', $amc)->exists()) continue;
            DB::table('mf_portfolio_sources')->insert(['amc' => $amc, 'page_url' => $url, 'file_pattern' => $pattern, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            $n++;
        }
        return $n;
    }

    /** @return array{files: int, schemes: int, scored: int, status: string} */
    public function run(object $source, ?callable $log = null): array
    {
        $log ??= fn () => null;
        $out = ['files' => 0, 'schemes' => 0, 'scored' => 0, 'status' => ''];
        try {
            $html = Http::withHeaders(['User-Agent' => self::UA, 'Accept' => 'text/html,*/*'])->timeout(45)->get($source->page_url)->throw()->body();
            $links = $this->links($html, $source->page_url, (string) $source->file_pattern);
            if (!$links) { $out['status'] = 'No portfolio files found on page'; return $out; }
            $latest = collect($links)->max('month');
            $pick = collect($links)->filter(fn ($l) => $latest ? $l['month'] === $latest : true)->take(150);
            foreach ($pick as $l) {
                $hash = sha1($l['url']);
                if (DB::table('mf_portfolio_files')->where('url_hash', $hash)->where('status', 'parsed')->exists()) continue;
                $res = $this->importFile($source, $l['url'], $l['month'] ? Carbon::parse($l['month']) : null);
                $out['files']++; $out['schemes'] += $res['schemes']; $out['scored'] += $res['scored'];
                $log(basename(urldecode(parse_url($l['url'], PHP_URL_PATH))) . ': ' . $res['schemes'] . ' schemes, ' . $res['scored'] . ' scored' . ($res['error'] ? ' — ' . $res['error'] : ''));
            }
            $out['status'] = ($latest ? 'Latest ' . Carbon::parse($latest)->format('M Y') : 'Undated files') . ': ' . $out['files'] . ' new file(s), ' . $out['scored'] . ' schemes scored';
            DB::table('mf_portfolio_sources')->where('id', $source->id)->update(['last_month' => $latest, 'last_status' => mb_substr($out['status'], 0, 300), 'last_checked_at' => now(), 'updated_at' => now()]);
        } catch (Throwable $e) {
            $out['status'] = 'Error: ' . mb_substr($e->getMessage(), 0, 250);
            DB::table('mf_portfolio_sources')->where('id', $source->id)->update(['last_status' => $out['status'], 'last_checked_at' => now()]);
        }
        return $out;
    }

    /** Downloads, parses and scores one file (xlsx, or a zip of xlsx). */
    public function importFile(object $source, string $url, ?Carbon $month): array
    {
        $hash = sha1($url);
        DB::table('mf_portfolio_files')->updateOrInsert(['url_hash' => $hash], ['source_id' => $source->id, 'amc' => $source->amc, 'file_url' => mb_substr($url, 0, 1000),
            'as_of' => $month?->toDateString(), 'status' => 'pending', 'fetched_at' => now(), 'updated_at' => now(), 'created_at' => now()]);
        $fileId = DB::table('mf_portfolio_files')->where('url_hash', $hash)->value('id');
        $tmp = storage_path('app/mf-portfolios');
        if (!is_dir($tmp)) mkdir($tmp, 0775, true);
        $path = "$tmp/$hash.bin";
        try {
            Http::withHeaders(['User-Agent' => self::UA])->timeout(120)->sink($path)->get($url)->throw();
            $books = $this->workbooks($path, $tmp, $hash);
            if (!$books) throw new \RuntimeException('No xlsx workbook in download');
            $schemes = []; $asOf = $month;
            foreach ($books as $book) {
                $sheets = $this->reader->read($book);
                $asOf ??= $this->asOfFromSheets($sheets);
                foreach ($this->parser->parse($sheets, $asOf) as $s) $schemes[] = $s;
            }
            $asOf ??= now()->subMonthNoOverflow()->endOfMonth();
            $now = now(); $rows = []; $aliases = [];
            foreach ($schemes as $s) {
                $key = PortfolioParser::key($s['scheme']);
                if ($key === '') continue;
                if ($s['aliases']) $aliases[$key] = PortfolioParser::key($s['aliases'][0]);
                foreach ($s['holdings'] as $h) $rows[] = $h + ['file_id' => $fileId, 'amc' => $source->amc, 'as_of' => $asOf->toDateString(), 'scheme_name' => mb_substr($s['scheme'], 0, 200),
                    'scheme_key' => mb_substr($key, 0, 160), 'created_at' => $now, 'updated_at' => $now];
            }
            DB::transaction(function () use ($fileId, $rows) {
                DB::table('mf_portfolio_holdings')->where('file_id', $fileId)->delete();
                foreach (array_chunk($rows, 500) as $chunk) DB::table('mf_portfolio_holdings')->insert($chunk);
            });
            $scored = $this->quality->scoreFile($fileId, $aliases);
            DB::table('mf_portfolio_files')->where('id', $fileId)->update(['status' => 'parsed', 'as_of' => $asOf->toDateString(), 'schemes' => count($schemes), 'holdings' => count($rows), 'error' => null, 'updated_at' => now()]);
            return ['schemes' => count($schemes), 'scored' => $scored, 'error' => null];
        } catch (Throwable $e) {
            DB::table('mf_portfolio_files')->where('id', $fileId)->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 500), 'updated_at' => now()]);
            return ['schemes' => 0, 'scored' => 0, 'error' => mb_substr($e->getMessage(), 0, 160)];
        } finally {
            foreach (glob("$tmp/$hash*") ?: [] as $f) @unlink($f);
        }
    }

    /** Portfolio file links on a disclosure page, each with the portfolio month read from its name. */
    public function links(string $html, string $base, string $pattern): array
    {
        $h = str_replace(['\\/', '\\"', '&', '&amp;'], ['/', '"', '&', '&'], $html);
        preg_match_all('~(?:https?://|/)[^"\'\s<>]+?\.(?:xlsx|zip)(?=["\'\s<>?#])~i', $h, $m);
        $out = [];
        foreach (array_unique($m[0]) as $l) {
            $abs = str_starts_with($l, 'http') ? $l : rtrim(preg_replace('#^(https?://[^/]+).*$#', '$1', $base), '/') . '/' . ltrim($l, '/');
            $abs = str_replace(' ', '%20', $abs);
            $name = urldecode($abs);
            if ($pattern !== '' && @preg_match($pattern, $name) !== 1) continue;
            if (preg_match('/half[- _]?year|fortnight|annual|aaum|riskometer|risk-o-meter|factsheet/i', $name)) continue;
            $out[$abs] = ['url' => $abs, 'month' => $this->monthOf(basename($name)) ?? $this->monthOf($name)];
        }
        return array_values($out);
    }

    /** Month-end date written in a file name: "30-09-2026", "30th September 2026", "september-2026", "September_30_2026". */
    public function monthOf(string $s): ?string
    {
        $s = strtolower($s);
        if (preg_match('/(?<!\d)(\d{1,2})[-_. ](\d{1,2})[-_. ](20\d{2})(?!\d)/', $s, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) return Carbon::create((int) $m[3], (int) $m[2], 1)->endOfMonth()->toDateString();
        if (preg_match('/(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*[-_ ,%20]*(?:\d{1,2}(?:st|nd|rd|th)?[-_ ,]*)?(20\d{2})/', $s, $m)) return Carbon::create((int) $m[2], self::MONTHS[$m[1]], 1)->endOfMonth()->toDateString();
        if (preg_match('/(?<!\d)(20\d{2})[-_](\d{2})[-_](\d{2})(?!\d)/', $s, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) return Carbon::create((int) $m[1], (int) $m[2], 1)->endOfMonth()->toDateString();
        return null;
    }

    private function workbooks(string $path, string $tmp, string $hash): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) return [];
        if ($zip->locateName('xl/workbook.xml') !== false) { $zip->close(); return [$path]; }
        $out = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('/\.xlsx$/i', $name) || str_contains($name, '__MACOSX')) continue;
            $dest = "$tmp/{$hash}_$i.xlsx";
            file_put_contents($dest, $zip->getFromIndex($i));
            $out[] = $dest;
        }
        $zip->close();
        return $out;
    }

    private function asOfFromSheets(array $sheets): ?Carbon
    {
        foreach ($sheets as $rows) foreach (array_slice($rows, 0, 25) as $row) foreach ($row as $c) {
            if (preg_match('/portfolio (as on|as at|statement as on)\s*:?\s*(.+)$/i', $c, $m) && ($d = $this->monthOf($m[2]))) return Carbon::parse($d);
        }
        return null;
    }
}
