<?php

namespace App\Services\Approvals\Drivers;

use Illuminate\Support\Facades\Http;

/**
 * Invest Uttarakhand single-window (SWCS): service listing with department, stage, applicability filters and official
 * PDFs; the per-service "Documents CheckList" popup; the Policies page and the Acts / Rules table.
 */
class SwcsUttarakhand
{
    public const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124 Safari/537.36';

    public function __construct(private object $source) {}

    private function base(): string
    {
        return rtrim($this->source->portal_url, '/');
    }

    private function abs(?string $href): ?string
    {
        if (!$href || stripos($href, 'javascript') === 0 || $href === '#') return null;
        $href = html_entity_decode(trim($href));
        if (preg_match('~^https?://~i', $href)) return $href;
        return $this->base() . '/' . ltrim(str_replace(' ', '%20', $href), '/');
    }

    private static function clean(string $html): string
    {
        return trim(preg_replace('~\s+~u', ' ', html_entity_decode(strip_tags(str_ireplace(['<br>', '<br/>', '<br />', '</br>'], ' ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }

    private static function list(string $html): array
    {
        $t = self::clean($html);
        return $t === '' || strcasecmp($t, 'N.A') === 0 ? [] : array_values(array_filter(array_map('trim', explode(',', $t))));
    }

    public function get(string $url): string
    {
        return Http::withHeaders(['User-Agent' => self::UA])->timeout(60)->retry(2, 1500)->get($url)->throw()->body();
    }

    /** @return array<int, array> one row per service / sub-service */
    public function services(): array
    {
        $html = $this->get($this->source->services_url);
        $parts = preg_split("~<input type=\"hidden\" name=\"service_id\" value='(\d+)'\s*/>\s*<input type=\"hidden\" name=\"sub_service_id\" value='(\d+)'\s*/>~", $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = [];
        for ($i = 1; $i + 2 < count($parts); $i += 3) {
            [$sid, $sub, $chunk] = [$parts[$i], $parts[$i + 1], $parts[$i + 2]];
            if (!preg_match('~<tr>(.*?)</tr>~s', $chunk, $tr)) continue;
            preg_match_all('~<td[^>]*>(.*?)</td>~s', $tr[1], $td);
            $c = $td[1];
            if (count($c) < 12) continue;
            $field = fn ($label) => preg_match('~<b>\s*' . $label . '\s*:\s*</b>(.*?)(?=<b>|$)~s', $c[1], $m) ? self::clean($m[1]) : null;
            $href = fn ($cell, $pattern = null) => preg_match('~href="([^"]+)"~', $cell, $m) && (!$pattern || preg_match($pattern, $m[1])) ? $this->abs($m[1]) : null;
            $stages = preg_match('~<b>\s*Service Incidence\s*:\s*</b>(.*?)<b>~s', $c[1], $m) ? array_values(array_filter(array_map(fn ($s) => self::clean($s), preg_split('~<br\s*/?>~i', $m[1])))) : [];
            $out[] = [
                'external_id' => $sid . '-' . $sub,
                'service_id' => $sid, 'sub_service_id' => $sub,
                'department' => self::clean($c[0]) ?: null,
                'name' => $field('Service Name') ?: 'Service ' . $sid,
                'service_type' => $field('Service Type') ?: null,
                'stages' => $stages,
                'integration' => $field('Service Integration Status') ?: null,
                'checklist_url' => $href($c[2], '~documentchecklist~i'),
                'form_url' => $href($c[3]),
                'fee_url' => $href($c[4]),
                'sop_url' => $href($c[5]),
                'timeline_url' => $href($c[6]),
                'inspection_url' => $href($c[7]),
                'risk_categories' => self::list($c[8]),
                'unit_sizes' => self::list($c[9]),
                'locations' => self::list($c[10]),
                'investor_types' => self::list($c[11]),
            ];
        }
        return $out;
    }

    /** @return array<int, array{code: ?string, doc_type: ?string, issued_by: ?string, name: string, is_required: ?bool, condition: ?string}> */
    public function documents(array $service): array
    {
        $html = Http::withHeaders(['User-Agent' => self::UA, 'X-Requested-With' => 'XMLHttpRequest'])->asForm()->timeout(45)->retry(2, 1500)
            ->post($this->base() . '/site/getRequiredDocuments/', ['service_id' => $service['service_id'], 'sub_service_id' => $service['sub_service_id']])->throw()->body();
        preg_match_all('~<tr>\s*<td>\d+</td>(.*?)</tr>~s', $html, $rows);
        $out = [];
        foreach ($rows[1] as $row) {
            preg_match_all('~<td[^>]*>(.*?)</td>~s', $row, $td);
            $c = array_map(fn ($x) => self::clean($x), $td[1]);
            if (count($c) < 5 || $c[3] === '') continue;
            $na = fn ($v) => $v === '' || in_array(strtolower($v), ['na', 'n.a', 'not available'], true) ? null : $v;
            $out[] = ['code' => $na($c[0]), 'doc_type' => $na($c[1]), 'issued_by' => $na($c[2]), 'name' => $c[3],
                'is_required' => match (strtoupper($c[4] ?? '')) { 'Y' => true, 'N' => false, default => null }, 'condition' => $na($c[5] ?? '')];
        }
        return $out;
    }

    /** @return array<int, array{kind: string, title: string, department: ?string, url: string, language: ?string}> */
    public function policies(): array
    {
        $out = [];
        if ($this->source->policies_url) {
            $html = $this->get($this->source->policies_url);
            preg_match_all('~<h3>(.*?)</h3>\s*<h5>(.*?)</h5>(.*?)(?=<div class="col-sm-12 col-lg-4|</section>|$)~s', $html, $m, PREG_SET_ORDER);
            foreach ($m as [, $title, $dept, $rest]) {
                preg_match_all('~<a class="dropdown-item" href="([^"]+)"[^>]*>([^<]+)</a>~', $rest, $links, PREG_SET_ORDER);
                foreach ($links as [, $href, $lang]) {
                    if ($url = $this->abs($href)) $out[] = ['kind' => 'policy', 'title' => self::clean($title), 'department' => self::clean(preg_replace('~^State\s*-\s*~', '', $dept)) ?: null, 'url' => $url, 'language' => self::clean($lang)];
                }
            }
        }
        if ($this->source->acts_url) {
            $html = $this->get($this->source->acts_url);
            if (preg_match('~<table[^>]*id="actTable"[^>]*>(.*?)</table>~s', $html, $t)) {
                preg_match_all('~<tr>\s*<th scope="row">\d+</th>(.*?)</tr>~s', $t[1], $rows);
                $langs = ['Brief', 'English', 'Hindi', 'Provision (English)', 'Provision (Hindi)'];
                foreach ($rows[1] as $row) {
                    preg_match_all('~<td[^>]*>(.*?)</td>~s', $row, $td);
                    $c = $td[1];
                    if (count($c) < 3) continue;
                    $title = self::clean($c[0]);
                    $kind = preg_match('~\b(rules?|niyamavali)\b~i', $title) ? 'rule' : (preg_match('~notification|order|g\.?o\.?\b~i', $title) ? 'notification' : 'act');
                    foreach (array_slice($c, 2) as $j => $cell) {
                        if (preg_match('~href="([^"]+)"~', $cell, $h) && ($url = $this->abs($h[1]))) {
                            $out[] = ['kind' => $kind, 'title' => $title, 'department' => self::clean(preg_replace('~^\s*(State|Central)\s*-\s*~', '', $c[1])) ?: null, 'url' => $url, 'language' => $langs[$j] ?? null];
                        }
                    }
                }
            }
        }
        return $out;
    }

    public function pdf(string $url): ?string
    {
        $res = Http::withHeaders(['User-Agent' => self::UA])->withOptions(['allow_redirects' => false])->timeout(60)->retry(2, 1500, throw: false)->get($url);
        return $res->successful() && str_starts_with($res->body(), '%PDF') ? $res->body() : null;
    }
}
